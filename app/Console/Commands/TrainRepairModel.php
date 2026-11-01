<?php

namespace App\Console\Commands;

use App\Models\Maintenance;
use App\Services\RepairTimePredictor;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TrainRepairModel extends Command
{
    protected $signature = 'repair:train';

    protected $description = 'Export the real repairs and retrain the repair time ML model';

    public function handle(RepairTimePredictor $predictor): int
    {
        // Real history: maintenances planned from an incident whose real duration is known.
        $repairs = Maintenance::with('incident.equipment.category')
            ->whereNotNull('incident_id')->whereNotNull('duration_days')->get()
            ->filter(fn (Maintenance $maintenance) => $maintenance->incident);

        $directory = base_path('ml/data');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $file = fopen($directory.'/repair_history_real.csv', 'w');
        fputcsv($file, ['description', 'severity', 'category', 'action', 'past_incidents', 'past_maintenances', 'photos', 'days']);
        foreach ($repairs as $maintenance) {
            $row = ['severity' => $maintenance->incident->severity, 'action' => $maintenance->type] + $predictor->features($maintenance->incident);
            fputcsv($file, [$row['description'], $row['severity'], $row['category'], $row['action'], $row['past_incidents'], $row['past_maintenances'], $row['photos'], $maintenance->duration_days]);
        }
        fclose($file);
        $this->info("{$repairs->count()} real repair(s) exported.");

        $process = new Process([config('services.ml.python') ?: 'python', base_path('ml/train_repair_model.py')]);
        $process->setTimeout(600)->run(fn ($type, $output) => $this->output->write($output));

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
