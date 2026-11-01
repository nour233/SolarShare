<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AiMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Affiche les métriques du modèle d\'IA de détection de fraude et de scoring';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $metricsPath = base_path('ml/model_metrics.json');

        if (!file_exists($metricsPath)) {
            $this->error('Fichier des métriques introuvable : ml/model_metrics.json');
            $this->warn('Exécutez "python ml/train_fraud_model.py" pour entraîner le modèle et générer les métriques.');
            return Command::FAILURE;
        }

        $metrics = json_decode(file_get_contents($metricsPath), true);

        if (!$metrics) {
            $this->error('Impossible de lire les métriques JSON.');
            return Command::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=yellow;options=bold>======================================================================</>');
        $this->line('<fg=cyan;options=bold>          🤖 SOLARSHARE - PERFORMANCE DU MODÈLE IA & FRAUDE          </>');
        $this->line('<fg=yellow;options=bold>======================================================================</>');
        $this->newLine();

        $this->line("<fg=white;options=bold>Modèle :</> <fg=green>{$metrics['model_name']}</> (<fg=gray>Arbres : {$metrics['n_estimators']}, Profondeur max : {$metrics['max_depth']}</>)");
        $this->line("<fg=white;options=bold>Dernier entraînement :</> <fg=yellow>{$metrics['trained_at']}</>");
        $this->line("<fg=white;options=bold>Échantillons :</> <fg=cyan>{$metrics['train_samples']} entraînement</> | <fg=cyan>{$metrics['test_samples']} test</>");
        $this->newLine();

        // 1. Core KPIs
        $this->info('📊 MÉTRIQUES CLÉS DE PERFORMANCE :');
        $this->table(
            ['Métrique', 'Valeur', 'Interprétation'],
            [
                ['ROC-AUC Score', $metrics['roc_auc'] . ' / 1.00', 'Excellente capacité de séparation fraude vs légitime'],
                ['Exactitude (Accuracy)', $metrics['accuracy'] . ' %', 'Taux global de prédictions correctes'],
                ['Précision (Precision)', $metrics['precision'] . ' %', 'Fiabilité des alertes de risque'],
                ['Rappel (Recall)', $metrics['recall'] . ' %', 'Proportion de fraudes correctement détectées'],
                ['F1-Score', $metrics['f1_score'] . ' / 1.00', 'Équilibre harmonieux précision / rappel'],
            ]
        );

        $this->newLine();

        // 2. Confusion Matrix
        $cm = $metrics['confusion_matrix'];
        $this->info('🎯 MATRICE DE CONFUSION (Sur ' . $metrics['test_samples'] . ' locations tests) :');
        $this->table(
            ['', 'Prédit Légitime (0)', 'Prédit Risqué (1)'],
            [
                ['Réel Légitime (0)', "<fg=green>{$cm['true_negatives']} (Vrais Négatifs)</>", "<fg=red>{$cm['false_positives']} (Fausses Alertes)</>"],
                ['Réel Risqué (1)', "<fg=yellow>{$cm['false_negatives']} (Fraudes Manquées)</>", "<fg=green>{$cm['true_positives']} (Fraudes Détectées)</>"],
            ]
        );

        $this->newLine();

        // 3. Feature Importances
        if (!empty($metrics['feature_importances'])) {
            $this->info('⚖️  IMPORTANCE DES VARIABLES (Features) :');
            $rows = [];
            foreach ($metrics['feature_importances'] as $feature => $weight) {
                $barLength = (int) round($weight / 2);
                $bar = str_repeat('█', $barLength) . str_repeat('░', max(0, 25 - $barLength));
                $rows[] = [$feature, $weight . ' %', "<fg=cyan>{$bar}</>"];
            }
            $this->table(['Variable', 'Poids', 'Visualisation'], $rows);
        }

        $this->newLine();
        $this->line('<fg=gray>Note : Pour ré-entraîner ce modèle avec de nouvelles données, exécutez "python ml/train_fraud_model.py".</>');
        $this->newLine();

        return Command::SUCCESS;
    }
}

