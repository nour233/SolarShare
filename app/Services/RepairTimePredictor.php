<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Predicts how many days the repair of an incident will take, with the offline
 * Random Forest model trained by ml/train_repair_model.py (no API, no internet).
 * Falls back to the average time per intervention type if Python is unavailable.
 */
class RepairTimePredictor
{
    public const MODEL_PATH = 'ml/repair_time_model.joblib';

    public const METRICS_PATH = 'ml/repair_metrics.json';

    private const DEFAULT_AVERAGES = ['nettoyage' => 1, 'inspection' => 3, 'reparation' => 6];

    public function predict(Incident $incident): array
    {
        $features = $this->features($incident);

        if ($prediction = $this->runModel($features)) {
            return $prediction + ['source' => 'ml'];
        }

        $days = max(1, (int) round(self::averages()[$features['action']] ?? 3));

        return ['days' => $days, 'min' => max(1, $days - 1), 'max' => $days + 1, 'source' => 'moyenne'];
    }

    /** Same columns as the training data. */
    public function features(Incident $incident): array
    {
        $incident->loadMissing('equipment.category');
        $diagnosis = $incident->ai_diagnosis ?? [];

        return [
            'description' => $incident->description,
            'severity' => $diagnosis['severity'] ?? $incident->severity,
            'category' => $incident->equipment->category?->name,
            'action' => $diagnosis['action'] ?? 'inspection',
            'past_incidents' => $incident->equipment->incidents()->whereKeyNot($incident->id)->count(),
            'past_maintenances' => $incident->equipment->maintenances()->count(),
            'photos' => count($incident->photos ?? []),
        ];
    }

    public static function metrics(): ?array
    {
        $path = base_path(self::METRICS_PATH);

        return is_file($path) ? json_decode(file_get_contents($path), true) : null;
    }

    private static function averages(): array
    {
        return self::metrics()['action_average_days'] ?? self::DEFAULT_AVERAGES;
    }

    private function runModel(array $features): ?array
    {
        $python = config('services.ml.python');
        if (! $python || ! is_file(base_path(self::MODEL_PATH))) {
            return null;
        }

        try {
            $process = new Process([$python, base_path('ml/predict_repair.py')]);
            $process->setInput(json_encode($features, JSON_UNESCAPED_UNICODE))->setTimeout(20)->run();
            $result = json_decode(trim($process->getOutput()), true);

            if ($process->isSuccessful() && isset($result['days'], $result['min'], $result['max'])) {
                return ['days' => (int) $result['days'], 'min' => (int) $result['min'], 'max' => (int) $result['max']];
            }
            Log::warning('Repair time model failed: '.($result['error'] ?? $process->getErrorOutput()));
        } catch (\Throwable $exception) {
            Log::warning('Repair time model failed: '.$exception->getMessage());
        }

        return null;
    }
}
