<?php

namespace App\Services;

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FraudScoringService
{
    /**
     * Compute risk score between 0 and 100 with clear explainable factors.
     * Uses Scikit-Learn Random Forest model via Python Process with automatic fallback.
     *
     * @return array{score: int, level: string, factors: array<string>, algorithm: string}
     */
    public function evaluate(Rental $rental): array
    {
        $rental->loadMissing(['renter', 'equipment']);
        $renter = $rental->renter;
        $equipment = $rental->equipment;

        $created = $rental->created_at ?? now();
        $start = Carbon::parse($rental->start_date);
        $hoursUntilStart = max(0.5, (float) $created->diffInHours($start, false));
        $durationDays = max(1, (int) $rental->durationDays());
        $totalPrice = (float) $rental->total_price;
        $deposit = (float) ($equipment?->deposit ?? 0.0);

        $pastRentals = 0;
        $cancelledCount = 0;
        $reclamationsCount = 0;

        if ($renter) {
            $pastRentals = $renter->rentals()->where('id', '!=', $rental->id)->count();
            $cancelledCount = $renter->rentals()->where('id', '!=', $rental->id)->where('status', 'cancelled')->count();
            $reclamationsCount = $renter->reclamations()->count();
        }

        $inputPayload = [
            'duration_days'           => $durationDays,
            'total_price'             => $totalPrice,
            'deposit_amount'          => $deposit,
            'hours_before_start'      => round($hoursUntilStart, 2),
            'user_past_rentals'       => $pastRentals,
            'user_cancelled_count'    => $cancelledCount,
            'user_reclamations_count' => $reclamationsCount,
            'deposit_to_price_ratio'  => $totalPrice > 0 ? round($deposit / $totalPrice, 4) : 1.0,
        ];

        // 1. Try Python Machine Learning model
        $mlResult = $this->runPythonML($inputPayload);
        if ($mlResult !== null) {
            return $mlResult;
        }

        // 2. Fallback heuristic rule-based engine if Python is unavailable
        return $this->evaluateRuleBased($inputPayload);
    }

    /**
     * Invoke the Scikit-Learn Random Forest Python model.
     */
    private function runPythonML(array $payload): ?array
    {
        try {
            $scriptPath = base_path('ml/predict_fraud.py');
            if (!file_exists($scriptPath)) {
                return null;
            }

            $b64Payload = 'b64:' . base64_encode(json_encode($payload));

            // Use python command or system python
            $pythonBin = 'python';
            $process = new Process([$pythonBin, $scriptPath, $b64Payload]);
            $process->setTimeout(6.0);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('Python ML process failed: ' . $process->getErrorOutput());
                return null;
            }

            $output = trim($process->getOutput());
            $result = json_decode($output, true);

            if (is_array($result) && isset($result['score'])) {
                return [
                    'score'     => (int) $result['score'],
                    'level'     => $result['level'] ?? 'low',
                    'factors'   => $result['factors'] ?? [],
                    'algorithm' => $result['algorithm'] ?? 'Random Forest (Scikit-Learn ML)',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Python ML execution error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fallback heuristic rule-based evaluation.
     */
    private function evaluateRuleBased(array $data): array
    {
        $score = 15;
        $factors = [];

        if ($data['hours_before_start'] <= 24) {
            $score += 25;
            $factors[] = "Réservation de dernière minute (< 24h avant le début) (+25)";
        }

        if ($data['total_price'] > 500) {
            $score += 20;
            $factors[] = "Montant de réservation élevé (> 500 €) (+20)";
        }

        if ($data['deposit_amount'] > 300) {
            $score += 15;
            $factors[] = "Matériel à forte caution (> 300 €) (+15)";
        }

        if ($data['user_past_rentals'] === 0) {
            $score += 15;
            $factors[] = "Nouveau compte client sans historique de location (+15)";
        } elseif ($data['user_past_rentals'] >= 3) {
            $score -= 20;
            $factors[] = "Client régulier ({$data['user_past_rentals']} locations passées) (-20)";
        }

        if ($data['user_cancelled_count'] >= 2) {
            $score += 15;
            $factors[] = "Historique d'annulations répétées ({$data['user_cancelled_count']}) (+15)";
        }

        $finalScore = max(0, min(100, $score));
        $level = match (true) {
            $finalScore >= 65 => 'high',
            $finalScore >= 35 => 'medium',
            default           => 'low',
        };

        return [
            'score'     => $finalScore,
            'level'     => $level,
            'factors'   => $factors,
            'algorithm' => 'Moteur Heuristique Interne',
        ];
    }
}
