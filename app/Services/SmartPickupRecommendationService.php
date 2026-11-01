<?php

namespace App\Services;

use App\Models\PickupPoint;
use App\Models\Rental;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class SmartPickupRecommendationService
{
    private const WEIGHTS = [
        'distance' => 40,
        'capacity' => 35,
        'time' => 25,
    ];

    public function __construct(
        private readonly PickupPointOperationsService $operations
    ) {}

    /**
     * Rank eligible points with normalized, explainable factors.
     *
     * Capacity score is the percentage of all appointment capacity still free.
     * Distance is normalized against the farthest eligible point in this result
     * set. Time score falls linearly from 100 at the requested time to 0 at a
     * twelve-hour difference. Only supplied factors contribute their weights.
     *
     * @return list<array<string, mixed>>
     */
    public function recommend(
        Rental $rental,
        CarbonInterface $date,
        ?string $preferredTime = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): array {
        $this->validateCoordinates($latitude, $longitude);
        $date = Carbon::parse($date->format('Y-m-d'), config('app.timezone'));
        $this->assertRentalDate($rental, $date);

        $points = PickupPoint::query()
            ->where('status', 'active')
            ->with(['openingHours', 'closures' => fn ($query) => $query->whereDate('closure_date', $date)])
            ->orderBy('id')
            ->get();
        $capacityByPoint = $this->operations->utilizationForDate(
            $points,
            $date,
            (int) $rental->getKey()
        );

        $candidates = [];
        foreach ($points as $point) {
            $utilization = $capacityByPoint[$point->id];
            $now = Carbon::now(config('app.timezone'));
            $futureSlots = array_map(function (array $slot) use ($date): array {
                $slotDateTime = $date->copy()->setTimeFromTimeString($slot['start']);
                $slot['remaining'] = max(0, $slot['capacity'] - $slot['reserved']);
                $slot['start_at'] = $slotDateTime->toIso8601String();

                return $slot;
            }, array_values(array_filter(
                $utilization['slots'],
                fn (array $slot) => $date->copy()
                    ->setTimeFromTimeString($slot['start'])
                    ->greaterThan($now)
            )));
            $slots = array_values(array_filter($futureSlots, fn (array $slot) => $slot['remaining'] > 0));

            if ($slots === []) {
                continue;
            }

            $capacityTotal = array_sum(array_column($futureSlots, 'capacity'));
            $capacityRemaining = array_sum(array_column($futureSlots, 'remaining'));
            $distance = $latitude !== null && $point->latitude !== null && $point->longitude !== null
                ? $this->distanceKilometers($latitude, $longitude, (float) $point->latitude, (float) $point->longitude)
                : null;
            $timeDifference = $preferredTime === null
                ? null
                : min(array_map(
                    fn (array $slot) => abs($this->minutes($slot['start']) - $this->minutes($preferredTime)),
                    $slots
                ));

            $candidates[] = [
                'pickup_point_id' => $point->id,
                'name' => $point->name,
                'address' => $point->address,
                'city' => $point->city,
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'distance_km' => $distance === null ? null : round($distance, 2),
                'slots' => $slots,
                'remaining_capacity' => $capacityRemaining,
                'capacity_ratio' => $capacityTotal > 0 ? $capacityRemaining / $capacityTotal : 0,
                'time_difference' => $timeDifference,
                'preferred_time' => $preferredTime,
                'score' => 0,
                'score_breakdown' => [],
                'explanation' => [],
                'eligible' => true,
            ];
        }

        if ($candidates === []) {
            return [];
        }

        $distances = array_values(array_filter(array_column($candidates, 'distance_km'), fn ($value) => $value !== null));
        $maxDistance = $distances === [] ? null : max($distances);

        foreach ($candidates as &$candidate) {
            $factors = [
                'capacity' => $candidate['capacity_ratio'] * 100,
            ];

            if ($candidate['distance_km'] !== null) {
                $factors['distance'] = $maxDistance > 0
                    ? (1 - ($candidate['distance_km'] / $maxDistance)) * 100
                    : 100;
            }

            if ($preferredTime !== null) {
                $factors['time'] = max(0, (1 - ($candidate['time_difference'] / 720)) * 100);
            }

            $activeWeight = array_sum(array_intersect_key(self::WEIGHTS, $factors));
            $breakdown = [];
            $weightedScore = 0;
            foreach ($factors as $factor => $score) {
                $weightPercent = self::WEIGHTS[$factor] / $activeWeight * 100;
                $breakdown[$factor] = [
                    'score' => round($score, 1),
                    'weight_percent' => round($weightPercent, 1),
                    'contribution' => round($score * $weightPercent / 100, 1),
                ];
                $weightedScore += $breakdown[$factor]['contribution'];
            }

            $candidate['score'] = (int) round(min(100, max(0, $weightedScore)));
            $candidate['score_breakdown'] = $breakdown;
            $candidate['explanation'] = $this->explanations($candidate);
            unset(
                $candidate['capacity_ratio'],
                $candidate['time_difference'],
                $candidate['preferred_time']
            );
        }
        unset($candidate);

        usort($candidates, function (array $left, array $right): int {
            return ($right['score'] <=> $left['score'])
                ?: (($left['distance_km'] ?? PHP_FLOAT_MAX) <=> ($right['distance_km'] ?? PHP_FLOAT_MAX))
                ?: ($right['remaining_capacity'] <=> $left['remaining_capacity'])
                ?: ($left['pickup_point_id'] <=> $right['pickup_point_id']);
        });

        return $candidates;
    }

    public function distanceKilometers(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $latDifference = deg2rad($lat2 - $lat1);
        $lonDifference = deg2rad($lon2 - $lon1);
        $a = sin($latDifference / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDifference / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function assertRentalDate(Rental $rental, CarbonInterface $date): void
    {
        if (in_array($rental->status, ['returned', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'rental' => 'Cette location ne peut plus recevoir de réservation de retrait.',
            ]);
        }

        if ($date->lt($rental->start_date->startOfDay()) || $date->gt($rental->end_date->startOfDay())) {
            throw ValidationException::withMessages([
                'date' => 'La date de retrait doit être comprise dans la période de location.',
            ]);
        }

        if ($date->lt(Carbon::today(config('app.timezone')))) {
            throw ValidationException::withMessages([
                'date' => 'La date de retrait doit être aujourd’hui ou dans le futur.',
            ]);
        }
    }

    private function validateCoordinates(?float $latitude, ?float $longitude): void
    {
        if (($latitude === null) !== ($longitude === null)
            || ($latitude !== null && ($latitude < -90 || $latitude > 90))
            || ($longitude !== null && ($longitude < -180 || $longitude > 180))) {
            throw ValidationException::withMessages([
                'latitude' => 'Veuillez saisir des coordonnées GPS valides.',
            ]);
        }
    }

    private function explanations(array $candidate): array
    {
        $explanations = [];
        if ($candidate['distance_km'] !== null) {
            $explanations[] = 'Point de retrait situé à '.number_format($candidate['distance_km'], 2, ',', ' ').' km à vol d’oiseau.';
        }
        if (count($candidate['slots']) > 1) {
            $explanations[] = 'Plusieurs créneaux disponibles.';
        } else {
            $explanations[] = 'Un créneau est disponible.';
        }
        if ($candidate['preferred_time'] !== null) {
            $closest = collect($candidate['slots'])
                ->sortBy(fn (array $slot) => abs($this->minutes($slot['start']) - $this->minutes($candidate['preferred_time'])))
                ->first();
            $explanations[] = 'Créneau le plus proche de votre heure souhaitée : '.($closest['start'] ?? 'non déterminé').'.';
        }

        return $explanations;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }
}
