<?php

namespace App\Console\Commands;

use App\Services\RfmSegmentationService;
use App\Models\User;
use App\Notifications\AtRiskUserNotification;
use Illuminate\Console\Command;

class SegmentUsers extends Command
{
    protected $signature = 'users:segment {--notify : Send notifications to at-risk and lost users}';

    protected $description = 'Run RFM segmentation on all clients and classify them (VIP, Regular, At-Risk, Lost, New)';

    public function handle(RfmSegmentationService $service): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║       🔬  RFM Client Segmentation — SolarShare             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $this->info('⏳ Collecting user data and running segmentation...');
        $this->newLine();

        $result = $service->segmentAllUsers();
        $segments = $result['segments'];
        $distribution = $result['distribution'];
        $metrics = $result['metrics'];

        // Display method used
        $method = $metrics['method'] ?? 'unknown';
        $this->info("📊 Method: " . strtoupper($method));
        $this->info("👥 Total users analyzed: " . ($metrics['total_users'] ?? count($segments)));
        $this->newLine();

        // Display distribution
        $this->info('┌─────────────────────────────────────────────────┐');
        $this->info('│            📈 SEGMENT DISTRIBUTION              │');
        $this->info('├─────────────────────────────────────────────────┤');

        $segmentLabels = [
            'vip' => ['⭐ VIP        ', 'yellow'],
            'regular' => ['✅ Régulier   ', 'green'],
            'at_risk' => ['⚠️  À risque   ', 'yellow'],
            'lost' => ['❌ Perdu      ', 'red'],
            'new' => ['🆕 Nouveau    ', 'gray'],
        ];

        $total = max(1, array_sum($distribution));

        foreach ($segmentLabels as $key => [$label, $color]) {
            $count = $distribution[$key] ?? 0;
            $pct = round(($count / $total) * 100);
            $bar = str_repeat('█', (int)($pct / 5)) . str_repeat('░', 20 - (int)($pct / 5));
            $this->line("│  {$label}  {$bar}  {$count} ({$pct}%)");
        }

        $this->info('└─────────────────────────────────────────────────┘');
        $this->newLine();

        // Display per-user details
        $this->info('┌──────────────────────────────────────────────────────────────────────────────────┐');
        $this->info('│                          👤 USER SEGMENTATION DETAILS                           │');
        $this->info('├────┬──────────────────┬────────────┬─────┬─────┬─────┬───────┬─────────────────┤');
        $this->info('│ ID │ Name             │ Segment    │  R  │  F  │  M  │ Score │ Confidence      │');
        $this->info('├────┼──────────────────┼────────────┼─────┼─────┼─────┼───────┼─────────────────┤');

        foreach ($segments as $userId => $data) {
            $user = User::find($userId);
            $name = $user ? mb_substr($user->name, 0, 16) : 'User #' . $userId;
            $name = str_pad($name, 16);
            $seg = str_pad(ucfirst($data['segment']), 10);
            $r = str_pad($data['rfm_recency'], 3, ' ', STR_PAD_LEFT);
            $f = str_pad($data['rfm_frequency'], 3, ' ', STR_PAD_LEFT);
            $m = str_pad($data['rfm_monetary'], 3, ' ', STR_PAD_LEFT);
            $score = str_pad(number_format($data['rfm_total'], 2), 5, ' ', STR_PAD_LEFT);
            $conf = str_pad(($data['confidence'] * 100) . '%', 15);

            $this->line("│ " . str_pad($userId, 2, ' ', STR_PAD_LEFT) . " │ {$name} │ {$seg} │ {$r} │ {$f} │ {$m} │ {$score} │ {$conf} │");
        }

        $this->info('└────┴──────────────────┴────────────┴─────┴─────┴─────┴───────┴─────────────────┘');
        $this->newLine();

        // Notify at-risk and lost users if requested
        if ($this->option('notify')) {
            $this->notifyAtRiskUsers($segments);
        } else {
            $atRisk = ($distribution['at_risk'] ?? 0) + ($distribution['lost'] ?? 0);
            if ($atRisk > 0) {
                $this->warn("💡 {$atRisk} user(s) at-risk/lost. Run with --notify to send re-engagement notifications.");
            }
        }

        $this->info('✅ Segmentation complete! Results saved to database.');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Send notifications to at-risk and lost users.
     */
    private function notifyAtRiskUsers(array $segments): void
    {
        $count = 0;

        foreach ($segments as $userId => $data) {
            if (in_array($data['segment'], ['at_risk', 'lost'])) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new AtRiskUserNotification($data['segment'], $data));
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->info("📧 Sent {$count} re-engagement notification(s) to at-risk/lost users.");
        } else {
            $this->info("📧 No at-risk/lost users to notify.");
        }
    }
}

