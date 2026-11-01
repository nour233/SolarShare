<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\PickupPoint;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use LogicException;

class PickupPointOperationsService
{
    /**
     * Only planned pickup deliveries represent future appointments. Capacity
     * is measured per configured slot, not across the entire operating day.
     */
    private const CAPACITY_STATUSES = ['planned'];

    public function isOpenAt(PickupPoint $pickupPoint, CarbonInterface $dateTime): bool
    {
        if ($pickupPoint->status !== 'active') {
            return false;
        }

        if ($pickupPoint->closures()
            ->whereDate('closure_date', $dateTime->format('Y-m-d'))
            ->exists()) {
            return false;
        }

        $time = $dateTime->format('H:i:s');

        return $pickupPoint->openingHours()
            ->where('day_of_week', $dateTime->isoWeekday())
            ->where('opens_at', '<=', $time)
            ->where('closes_at', '>', $time)
            ->exists();
    }

    /**
     * Calculate per-point and per-slot utilization with one delivery query.
     *
     * Duplicate delivery rows for the same rental are treated as one
     * appointment. A 30-minute slot is the default; each point may configure
     * another duration between 15 and 120 minutes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function utilizationForDate(
        Collection $pickupPoints,
        CarbonInterface $date,
        ?int $excludeRentalId = null
    ): array
    {
        if ($pickupPoints->isEmpty()) {
            return [];
        }

        $pointIds = $pickupPoints->pluck('id')->all();
        $deliveries = Delivery::query()
            ->whereIn('pickup_point_id', $pointIds)
            ->where('type', 'pickup')
            ->whereDate('scheduled_date', $date->format('Y-m-d'))
            ->whereIn('status', self::CAPACITY_STATUSES)
            ->when($excludeRentalId !== null, fn ($query) => $query->where('rental_id', '!=', $excludeRentalId))
            ->orderBy('id')
            ->get(['id', 'rental_id', 'pickup_point_id', 'scheduled_time']);

        $reservationsByPoint = [];
        $seenRentals = [];

        foreach ($deliveries as $delivery) {
            if (isset($seenRentals[$delivery->rental_id])) {
                continue;
            }

            $seenRentals[$delivery->rental_id] = true;
            $reservationsByPoint[$delivery->pickup_point_id][] = $delivery;
        }

        $result = [];
        foreach ($pickupPoints as $pickupPoint) {
            $result[$pickupPoint->id] = $this->calculatePointUtilization(
                $pickupPoint,
                $date,
                collect($reservationsByPoint[$pickupPoint->id] ?? [])
            );
        }

        return $result;
    }

    /**
     * Return only slots with capacity remaining, excluding slots already past.
     *
     * @return list<array{start: string, end: string, reserved: int, capacity: int, remaining: int, occupancy_percent: int}>
     */
    public function availableSlotsForDate(
        PickupPoint $pickupPoint,
        CarbonInterface $date,
        ?int $excludeRentalId = null,
        ?CarbonInterface $now = null
    ): array {
        $now ??= Carbon::now(config('app.timezone'));
        $date = Carbon::parse($date->format('Y-m-d'), config('app.timezone'));
        $utilization = $this->utilizationForDate(
            collect([$pickupPoint->loadMissing(['openingHours', 'closures'])]),
            $date,
            $excludeRentalId
        )[$pickupPoint->id];

        return array_values(array_filter(
            array_map(function (array $slot) use ($date, $now): array {
                $slotDateTime = $date->copy()->setTimeFromTimeString($slot['start']);
                $slot['remaining'] = max(0, $slot['capacity'] - $slot['reserved']);
                $slot['start_at'] = $slotDateTime->toIso8601String();

                return $slot;
            }, $utilization['slots']),
            fn (array $slot) => $slot['remaining'] > 0
                && Carbon::parse($slot['start_at'])->greaterThan($now)
        ));
    }

    private function calculatePointUtilization(
        PickupPoint $pickupPoint,
        CarbonInterface $date,
        Collection $reservations
    ): array {
        $closureExists = $this->hasClosureOn($pickupPoint, $date->format('Y-m-d'));

        $slots = [];
        if (! $closureExists && $pickupPoint->status === 'active') {
            foreach ($pickupPoint->openingHours as $openingHour) {
                if ((int) $openingHour->day_of_week !== $date->isoWeekday()) {
                    continue;
                }

                $slotDuration = (int) $pickupPoint->slot_duration_minutes;
                if ($slotDuration < 15 || $slotDuration > 120) {
                    throw new LogicException('Pickup point slot duration must be between 15 and 120 minutes.');
                }

                $intervalStart = $this->minutesAfterMidnight($openingHour->opens_at);
                $intervalEnd = $this->minutesAfterMidnight($openingHour->closes_at);

                for ($slotStart = $intervalStart; $slotStart < $intervalEnd; $slotStart += $slotDuration) {
                    $slotEnd = min($slotStart + $slotDuration, $intervalEnd);
                    $slots[] = [
                        'start' => $this->formatTime($slotStart),
                        'end' => $this->formatTime($slotEnd),
                        'reserved' => 0,
                        'capacity' => (int) $pickupPoint->capacity,
                    ];
                }
            }
        }

        $unslottedReservations = 0;
        foreach ($reservations as $reservation) {
            $appointmentTime = substr((string) $reservation->scheduled_time, 0, 5);
            $slotIndex = null;

            foreach ($slots as $index => $slot) {
                if ($appointmentTime >= $slot['start'] && $appointmentTime < $slot['end']) {
                    $slotIndex = $index;
                    break;
                }
            }

            if ($slotIndex === null) {
                $unslottedReservations++;

                continue;
            }

            $slots[$slotIndex]['reserved']++;
        }

        $totalSlotCapacity = count($slots) * (int) $pickupPoint->capacity;
        $slottedReservations = array_sum(array_column($slots, 'reserved'));
        $availableCapacity = array_sum(array_map(
            fn (array $slot) => max(0, $slot['capacity'] - $slot['reserved']),
            $slots
        ));

        foreach ($slots as &$slot) {
            $slot['occupancy_percent'] = $slot['capacity'] > 0
                ? (int) round($slot['reserved'] / $slot['capacity'] * 100)
                : 0;
            $slot['status'] = $slot['reserved'] >= $slot['capacity']
                ? 'full'
                : ($slot['occupancy_percent'] >= 80 ? 'nearly_full' : 'available');
        }
        unset($slot);

        $occupancyPercent = $totalSlotCapacity > 0
            ? (int) round($slottedReservations / $totalSlotCapacity * 100)
            : 0;

        return [
            'reservation_count' => $reservations->count(),
            'slotted_reservations' => $slottedReservations,
            'unslotted_reservations' => $unslottedReservations,
            'total_slot_capacity' => $totalSlotCapacity,
            'available_capacity' => $availableCapacity,
            'occupancy_percent' => $occupancyPercent,
            'full_slots' => collect($slots)->where('status', 'full')->count(),
            'nearly_full_slots' => collect($slots)->where('status', 'nearly_full')->count(),
            'slots' => $slots,
            'closed_for_date' => $closureExists,
            'inactive' => $pickupPoint->status !== 'active',
            'has_schedule' => $pickupPoint->openingHours
                ->contains('day_of_week', $date->isoWeekday()),
            'status' => $pickupPoint->status !== 'active'
                ? 'inactive'
                : ($closureExists
                    ? 'closed'
                    : ($totalSlotCapacity > 0 && $availableCapacity === 0
                        ? 'full'
                        : ($occupancyPercent >= 80 ? 'nearly_full' : 'available'))),
        ];
    }

    private function hasClosureOn(PickupPoint $pickupPoint, string $date): bool
    {
        if ($pickupPoint->relationLoaded('closures')) {
            return $pickupPoint->closures->contains(
                fn ($closure) => $closure->closure_date->format('Y-m-d') === $date
            );
        }

        return $pickupPoint->closures()
            ->whereDate('closure_date', $date)
            ->exists();
    }

    private function minutesAfterMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    private function formatTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
