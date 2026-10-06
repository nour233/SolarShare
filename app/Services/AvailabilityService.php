<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Check whether the given equipment is available for the requested dates.
     * Returns true if no active rental overlaps the requested window.
     */
    public function isAvailable(Equipment $equipment, Carbon $start, Carbon $end, ?int $excludeRentalId = null): bool
    {
        return ! $this->conflictingRentals($equipment, $start, $end, $excludeRentalId)->exists();
    }

    /**
     * Return a query of active rentals that overlap the requested window.
     */
    public function conflictingRentals(Equipment $equipment, Carbon $start, Carbon $end, ?int $excludeRentalId = null)
    {
        return $equipment->rentals()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->when($excludeRentalId, fn ($q) => $q->where('id', '!=', $excludeRentalId));
    }

    /**
     * Suggest the next available start date for the requested equipment + duration.
     * Scans forward from $after up to 90 days.
     *
     * @return Carbon|null  First available start date, or null if none found in window.
     */
    public function nextAvailableDate(Equipment $equipment, int $durationDays, Carbon $after): ?Carbon
    {
        // Load all future booked ranges once, sorted by start date
        $booked = $equipment->rentals()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->where('end_date', '>=', $after)
            ->orderBy('start_date')
            ->get(['start_date', 'end_date']);

        $candidate = $after->copy();
        $limit     = $after->copy()->addDays(90);

        while ($candidate->lte($limit)) {
            $candidateEnd = $candidate->copy()->addDays($durationDays - 1);

            $conflict = $booked->first(function ($rental) use ($candidate, $candidateEnd) {
                return $rental->start_date->lte($candidateEnd)
                    && $rental->end_date->gte($candidate);
            });

            if (! $conflict) {
                return $candidate;
            }

            // Jump to the day after this conflict ends
            $candidate = $conflict->end_date->copy()->addDay();
        }

        return null;
    }

    /**
     * Suggest alternative equipment from the same category that is available
     * for the requested dates. Returns up to $limit results.
     */
    public function alternativeEquipment(Equipment $equipment, Carbon $start, Carbon $end, int $limit = 3): Collection
    {
        // Fetch equipment in same category (excluding the requested one)
        $candidates = Equipment::where('category_id', $equipment->category_id)
            ->where('id', '!=', $equipment->id)
            ->with('category')
            ->get();

        return $candidates
            ->filter(fn ($alt) => $this->isAvailable($alt, $start, $end))
            ->take($limit)
            ->values();
    }

    /**
     * Build a full recommendation payload when a booking is impossible.
     * Returns an array with:
     *  - next_date: next available start date for the same duration on this equipment
     *  - alternatives: alternative equipment available for the original dates
     */
    public function recommend(Equipment $equipment, Carbon $start, Carbon $end): array
    {
        $duration    = $start->diffInDays($end) + 1;
        $nextDate    = $this->nextAvailableDate($equipment, $duration, Carbon::today());
        $alternatives = $this->alternativeEquipment($equipment, $start, $end);

        return [
            'next_date'    => $nextDate,
            'alternatives' => $alternatives,
        ];
    }

    /**
     * Return all booked date ranges for a given equipment as an array of
     * ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD'] for calendar rendering.
     */
    public function bookedRangesFor(Equipment $equipment, ?int $excludeRentalId = null): array
    {
        return $equipment->rentals()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->when($excludeRentalId, fn ($q) => $q->where('id', '!=', $excludeRentalId))
            ->get(['start_date', 'end_date'])
            ->map(fn ($r) => [
                'start' => $r->start_date->format('Y-m-d'),
                'end'   => $r->end_date->format('Y-m-d'),
            ])
            ->toArray();
    }
}
