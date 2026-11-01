<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rental;
use App\Models\Payment;
use App\Models\Incident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class RfmSegmentationService
{
    /**
     * Run RFM segmentation for all non-admin users.
     *
     * @return array{segments: array, distribution: array, metrics: array}
     */
    public function segmentAllUsers(): array
    {
        $users = User::where('role', '!=', 'admin')->get();
        $userData = $this->buildUserData($users);

        // Try Python ML first, fallback to PHP
        $result = $this->runPythonSegmentation($userData);

        if ($result === null) {
            Log::info('RFM: Python unavailable, using PHP fallback');
            $result = $this->phpFallbackSegmentation($userData);
        }

        // Save results to database
        $this->saveSegments($result['segments']);

        return $result;
    }

    /**
     * Segment a single user.
     */
    public function segmentUser(User $user): array
    {
        $userData = $this->buildUserData(collect([$user]));

        $result = $this->runPythonSegmentation($userData);
        if ($result === null) {
            $result = $this->phpFallbackSegmentation($userData);
        }

        $this->saveSegments($result['segments']);

        return $result['segments'][$user->id] ?? [];
    }

    /**
     * Build user data array for segmentation input.
     */
    private function buildUserData($users): array
    {
        $data = [];

        foreach ($users as $user) {
            // Get rental stats
            $rentals = Rental::where('renter_id', $user->id)->get();
            $rentalCount = $rentals->count();

            // Total spent (sum of paid payments across all rentals)
            $totalSpent = Payment::whereIn('rental_id', $rentals->pluck('id'))
                ->where('status', 'paid')
                ->sum('amount');

            // Days since last rental
            $lastRental = $rentals->sortByDesc('created_at')->first();
            $daysSinceLastRental = $lastRental
                ? Carbon::parse($lastRental->created_at)->diffInDays(now())
                : 9999;

            // Incident count
            $incidentCount = Incident::where('user_id', $user->id)->count();

            // Account age
            $accountAgeDays = Carbon::parse($user->created_at)->diffInDays(now());

            $data[] = [
                'id' => $user->id,
                'name' => $user->name,
                'rental_count' => $rentalCount,
                'total_spent' => (float) $totalSpent,
                'days_since_last_rental' => (int) $daysSinceLastRental,
                'incident_count' => $incidentCount,
                'account_age_days' => max(1, (int) $accountAgeDays),
            ];
        }

        return $data;
    }

    /**
     * Run segmentation via Python ML script.
     */
    private function runPythonSegmentation(array $userData): ?array
    {
        $scriptPath = base_path('ml/rfm_segmentation.py');

        if (!file_exists($scriptPath)) {
            return null;
        }

        $input = json_encode(['users' => $userData]);
        $encoded = 'b64:' . base64_encode($input);

        $pythonPaths = [
            'C:\\Users\\User\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
            'python',
            'python3',
        ];

        foreach ($pythonPaths as $python) {
            try {
                $process = new Process([$python, $scriptPath, $encoded]);
                $process->setTimeout(30);
                $process->run();

                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    $result = json_decode($output, true);
                    if (is_array($result) && isset($result['segments'])) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * PHP fallback segmentation (rule-based).
     */
    private function phpFallbackSegmentation(array $userData): array
    {
        $segments = [];

        // Thresholds
        $recencyThresholds = [7, 30, 90, 180];
        $frequencyThresholds = [1, 3, 6, 10];
        $monetaryThresholds = [100, 500, 1500, 5000];

        foreach ($userData as $user) {
            $rVal = $user['days_since_last_rental'];
            $fVal = $user['rental_count'];
            $mVal = $user['total_spent'];
            $incidents = $user['incident_count'];

            // Score Recency (5=recent, 1=old)
            if ($fVal == 0 || $rVal >= 9999) {
                $rScore = 1;
            } elseif ($rVal <= $recencyThresholds[0]) {
                $rScore = 5;
            } elseif ($rVal <= $recencyThresholds[1]) {
                $rScore = 4;
            } elseif ($rVal <= $recencyThresholds[2]) {
                $rScore = 3;
            } elseif ($rVal <= $recencyThresholds[3]) {
                $rScore = 2;
            } else {
                $rScore = 1;
            }

            // Score Frequency (5=many, 1=few)
            if ($fVal >= $frequencyThresholds[3]) {
                $fScore = 5;
            } elseif ($fVal >= $frequencyThresholds[2]) {
                $fScore = 4;
            } elseif ($fVal >= $frequencyThresholds[1]) {
                $fScore = 3;
            } elseif ($fVal >= $frequencyThresholds[0]) {
                $fScore = 2;
            } else {
                $fScore = 1;
            }

            // Score Monetary (5=high, 1=low)
            if ($mVal >= $monetaryThresholds[3]) {
                $mScore = 5;
            } elseif ($mVal >= $monetaryThresholds[2]) {
                $mScore = 4;
            } elseif ($mVal >= $monetaryThresholds[1]) {
                $mScore = 3;
            } elseif ($mVal >= $monetaryThresholds[0]) {
                $mScore = 2;
            } else {
                $mScore = 1;
            }

            // Total score with penalty
            $penalty = min($incidents * 0.3, 1.5);
            $total = round(($rScore * 0.35 + $fScore * 0.35 + $mScore * 0.30) - $penalty, 2);
            $total = max(0.5, min(5.0, $total));

            // Classify
            $segment = $this->classifySegment($rScore, $fScore, $mScore, $total, $fVal);

            $segments[$user['id']] = [
                'segment' => $segment,
                'rfm_recency' => $rScore,
                'rfm_frequency' => $fScore,
                'rfm_monetary' => $mScore,
                'rfm_total' => $total,
                'confidence' => round(min(1.0, 0.5 + ($fVal * 0.1)), 2),
                'details' => [
                    'days_since_last' => $rVal == 9999 ? null : $rVal,
                    'rental_count' => $fVal,
                    'total_spent' => $mVal,
                    'incident_count' => $incidents,
                ],
            ];
        }

        $distribution = ['vip' => 0, 'regular' => 0, 'at_risk' => 0, 'lost' => 0, 'new' => 0];
        foreach ($segments as $s) {
            $seg = $s['segment'];
            $distribution[$seg] = ($distribution[$seg] ?? 0) + 1;
        }

        return [
            'segments' => $segments,
            'distribution' => $distribution,
            'metrics' => [
                'method' => 'php_rule_based',
                'total_users' => count($userData),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Classify segment based on RFM scores.
     */
    private function classifySegment(int $r, int $f, int $m, float $total, int $rentalCount): string
    {
        if ($rentalCount == 0) return 'new';
        if ($total >= 3.8 && $r >= 4 && $f >= 3) return 'vip';
        if ($total >= 2.5 && $r >= 3) return 'regular';
        if ($f >= 2 && $r <= 2) return 'at_risk';
        if ($r <= 1 && $total < 2.5) return 'lost';
        if ($total >= 2.0) return 'regular';
        if ($r <= 2) return 'at_risk';
        return 'regular';
    }

    /**
     * Save segment results to the users table.
     */
    private function saveSegments(array $segments): void
    {
        foreach ($segments as $userId => $data) {
            User::where('id', $userId)->update([
                'rfm_segment' => $data['segment'],
                'rfm_recency_score' => $data['rfm_recency'],
                'rfm_frequency_score' => $data['rfm_frequency'],
                'rfm_monetary_score' => $data['rfm_monetary'],
                'rfm_total_score' => $data['rfm_total'],
                'rfm_total_spent' => $data['details']['total_spent'] ?? 0,
                'rfm_rental_count' => $data['details']['rental_count'] ?? 0,
                'rfm_days_since_last' => $data['details']['days_since_last'],
                'rfm_segmented_at' => now(),
            ]);
        }
    }

    /**
     * Get segment label in French.
     */
    public static function segmentLabel(string $segment): string
    {
        return match ($segment) {
            'vip' => '⭐ VIP',
            'regular' => '✅ Régulier',
            'at_risk' => '⚠️ À risque',
            'lost' => '❌ Perdu',
            'new' => '🆕 Nouveau',
            default => $segment,
        };
    }

    /**
     * Get segment color for badges.
     */
    public static function segmentColor(string $segment): string
    {
        return match ($segment) {
            'vip' => '#ffd700',
            'regular' => '#28a745',
            'at_risk' => '#fd7e14',
            'lost' => '#dc3545',
            'new' => '#6c757d',
            default => '#17a2b8',
        };
    }

    /**
     * Get segment badge class.
     */
    public static function segmentBadgeClass(string $segment): string
    {
        return match ($segment) {
            'vip' => 'warning',
            'regular' => 'success',
            'at_risk' => 'orange',
            'lost' => 'danger',
            'new' => 'secondary',
            default => 'info',
        };
    }
}

