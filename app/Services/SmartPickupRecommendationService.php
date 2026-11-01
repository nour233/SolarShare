<?php

namespace App\Services;

use App\Models\PickupPoint;
use App\Models\Rental;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class SmartPickupRecommendationService
{
    private const DISTANCE_REFERENCE_KM = 100;

    private const WEIGHTS = [
        'distance' => 40,
        'capacity' => 35,
        'time' => 25,
        'personalization' => 20,
    ];

    public function __construct(
        private readonly PickupPointOperationsService $operations
    ) {}

    /**
     * Rank eligible points with normalized, explainable factors.
     *
     * Capacity score is the percentage of future appointment capacity still
     * free. Distance score is max(0, 100 * (1 - km / 100)). Time score falls
     * linearly from 100 at the requested time to 0 at a twelve-hour difference.
     * A favorite point or exact city match is a soft ranking factor applied only
     * to points that already have eligible slots; it never changes availability.
     * Missing factors are removed and remaining weights are renormalized.
     *
     * @return list<array<string, mixed>>
     */
    public function recommend(
        Rental $rental,
        CarbonInterface $date,
        ?string $preferredTime = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $favoritePickupPointId = null,
        ?string $preferredCity = null,
        ?string $preferredStartTime = null,
        ?string $preferredEndTime = null
    ): array {
        $this->validateCoordinates($latitude, $longitude);
        $date = Carbon::parse($date->format('Y-m-d'), config('app.timezone'));
        $this->assertRentalDate($rental, $date);

        $now = Carbon::now(config('app.timezone'));
        $points = PickupPoint::query()
            ->where('status', 'active')
            ->with([
                'openingHours',
                'closures' => fn ($query) => $query->whereDate('closure_date', $date),
            ])
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
            if ($preferredTime === null && $preferredStartTime !== null && $preferredEndTime !== null) {
                $windowStart = $this->minutes($preferredStartTime);
                $windowEnd = $this->minutes($preferredEndTime);
                $timeDifference = min(array_map(function (array $slot) use ($windowStart, $windowEnd): int {
                    $slotStart = $this->minutes($slot['start']);
                    $slotEnd = $this->minutes($slot['end']);

                    if ($slotStart < $windowEnd && $slotEnd > $windowStart) {
                        return 0;
                    }

                    return $slotEnd <= $windowStart
                        ? $windowStart - $slotEnd
                        : $slotStart - $windowEnd;
                }, $slots));
            }
            $favoriteMatch = $favoritePickupPointId !== null
                && (int) $point->id === $favoritePickupPointId;
            $cityMatch = filled($preferredCity)
                && mb_strtolower(trim($point->city)) === mb_strtolower(trim($preferredCity));

            $candidates[] = [
                'pickup_point_id' => $point->id,
                'name' => $point->name,
                'address' => $point->address,
                'city' => $point->city,
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'distance_km' => $distance === null ? null : round($distance, 2),
                'distance_sort_km' => $distance,
                'slots' => $slots,
                'remaining_capacity' => $capacityRemaining,
                'capacity_ratio' => $capacityTotal > 0 ? $capacityRemaining / $capacityTotal : 0,
                'time_difference' => $timeDifference,
                'preferred_time' => $preferredTime,
                'preferred_start_time' => $preferredStartTime,
                'preferred_end_time' => $preferredEndTime,
                'favorite_match' => $favoriteMatch,
                'city_match' => $cityMatch,
                'score' => 0,
                'score_breakdown' => [],
                'explanation' => [],
                'eligible' => true,
            ];
        }

        if ($candidates === []) {
            return [];
        }

        foreach ($candidates as &$candidate) {
            $factors = [
                'capacity' => $candidate['capacity_ratio'] * 100,
            ];

            if ($candidate['distance_km'] !== null) {
                $factors['distance'] = max(
                    0,
                    (1 - ($candidate['distance_sort_km'] / self::DISTANCE_REFERENCE_KM)) * 100
                );
            }

            if ($preferredTime !== null || ($preferredStartTime !== null && $preferredEndTime !== null)) {
                $factors['time'] = max(0, (1 - ($candidate['time_difference'] / 720)) * 100);
            }
            if ($favoritePickupPointId !== null || filled($preferredCity)) {
                $factors['personalization'] = $candidate['favorite_match']
                    ? 100
                    : ($candidate['city_match'] ? 70 : 0);
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
                $candidate['preferred_time'],
                $candidate['preferred_start_time'],
                $candidate['preferred_end_time'],
                $candidate['favorite_match'],
                $candidate['city_match'],
                $candidate['distance_sort_km']
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
        $a = min(1, max(0, $a));

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function assertRentalDate(Rental $rental, CarbonInterface $date): void
    {
        if (in_array($rental->status, ['returned', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'rental' => 'Cette location ne peut plus recevoir de réservation de retrait.',
            ]);
        }

        if ($date->toDateString() < $rental->start_date->toDateString()
            || $date->toDateString() > $rental->end_date->toDateString()) {
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
        } elseif ($candidate['preferred_start_time'] !== null && $candidate['preferred_end_time'] !== null) {
            $explanations[] = 'Les créneaux proches de votre plage préférée ('.$candidate['preferred_start_time'].'–'.$candidate['preferred_end_time'].') sont favorisés.';
        }
        if ($candidate['favorite_match']) {
            $explanations[] = 'Votre point de retrait favori est disponible et a été favorisé dans le classement.';
        } elseif ($candidate['city_match']) {
            $explanations[] = 'Ce point correspond à votre ville préférée.';
        }

        return $explanations;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }
}
