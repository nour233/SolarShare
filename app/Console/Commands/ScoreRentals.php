<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Services\FraudScoringService;
use Illuminate\Console\Command;

class ScoreRentals extends Command
{
    protected $signature = 'rentals:score {rental_id?}';
    protected $description = 'Run the Python Random Forest ML model on existing rentals';

    public function handle(FraudScoringService $scorer): int
    {
        $rentalId = $this->argument('rental_id');

        $query = Rental::with(['renter', 'equipment']);
        if ($rentalId) {
            $query->where('id', $rentalId);
        }

        $rentals = $query->get();

        if ($rentals->isEmpty()) {
            $this->warn('No rentals found to score.');
            return self::FAILURE;
        }

        $this->info("Evaluating {$rentals->count()} rental(s) with Random Forest ML...");

        foreach ($rentals as $rental) {
            $result = $scorer->evaluate($rental);

            $rental->update([
                'risk_score'   => $result['score'],
                'risk_level'   => $result['level'],
                'risk_factors' => $result['factors'],
            ]);

            $this->line("--------------------------------------------------");
            $this->info("Rental #{$rental->id} ({$rental->equipment?->title})");
            $this->line("Client: {$rental->renter?->name} | Total: {$rental->total_price} €");
            $this->line("Algorithm: " . ($result['algorithm'] ?? 'N/A'));
            $this->line("Score: {$result['score']}/100 [Level: {$result['level']}]");

            if (!empty($result['factors'])) {
                $this->line("Factors:");
                foreach ($result['factors'] as $factor) {
                    $this->line("  • {$factor}");
                }
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Scoring complete!");
        return self::SUCCESS;
    }
}

