<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\PickupPoint;
use App\Models\Rental;
use App\Models\User;
use App\Notifications\PickupBookingConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryBookingService
{
    public function __construct(
        private readonly PickupPointOperationsService $operations,
        private readonly SmartPickupRecommendationService $recommendations
    ) {}

    /**
     * Confirm or reschedule the sole active planned pickup appointment.
     *
     * @return array{delivery: Delivery, changed: bool}
     */
    public function bookPickup(
        Rental $rental,
        int $pickupPointId,
        string $date,
        string $time,
        ?User $actor = null,
        ?Delivery $currentDelivery = null,
        float $deliveryFee = 0
    ): array {
        return DB::transaction(function () use (
            $rental,
            $pickupPointId,
            $date,
            $time,
            $actor,
            $currentDelivery,
            $deliveryFee
        ): array {
            $lockedRental = Rental::query()
                ->whereKey($rental->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $selectedDate = Carbon::parse($date, config('app.timezone'));
            $this->recommendations->assertRentalDate($lockedRental, $selectedDate);

            $point = PickupPoint::query()
                ->whereKey($pickupPointId)
                ->lockForUpdate()
                ->firstOrFail();
            $point->load([
                'openingHours',
                'closures' => fn ($query) => $query->whereDate('closure_date', $selectedDate),
            ]);

            $activeDeliveries = Delivery::query()
                ->where('rental_id', $lockedRental->id)
                ->whereIn('status', ['planned', 'in_transit', 'delivered'])
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($currentDelivery) {
                $currentDelivery = $activeDeliveries->firstWhere('id', $currentDelivery->id)
                    ?? Delivery::query()
                        ->whereKey($currentDelivery->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if ($currentDelivery->rental_id !== $lockedRental->id) {
                    throw ValidationException::withMessages([
                        'delivery' => 'Cette livraison ne correspond pas à la location sélectionnée.',
                    ]);
                }

                if ($currentDelivery->type !== 'pickup') {
                    throw ValidationException::withMessages([
                        'delivery' => 'Une livraison à domicile ne peut pas être convertie implicitement en retrait.',
                    ]);
                }

                if ($currentDelivery->status !== 'planned') {
                    throw ValidationException::withMessages([
                        'delivery' => 'Seul un rendez-vous de retrait planifié peut être modifié.',
                    ]);
                }
            }

            $existingPickup = $currentDelivery;
            foreach ($activeDeliveries as $activeDelivery) {
                if ($currentDelivery && $activeDelivery->is($currentDelivery)) {
                    continue;
                }

                if ($activeDelivery->type === 'pickup' && $activeDelivery->status === 'planned' && $existingPickup === null) {
                    $existingPickup = $activeDelivery;

                    continue;
                }

                $message = $activeDelivery->type === 'home_delivery'
                    ? 'Une livraison à domicile est déjà planifiée pour cette location.'
                    : 'Une livraison est déjà en cours ou terminée pour cette location.';
                throw ValidationException::withMessages(['delivery' => $message]);
            }

            if ($existingPickup && $existingPickup->status !== 'planned') {
                throw ValidationException::withMessages([
                    'delivery' => 'Une livraison terminée ne peut pas être remplacée.',
                ]);
            }

            $slotMetrics = $this->operations->utilizationForDate(
                collect([$point]),
                $selectedDate,
                (int) $lockedRental->id
            )[$point->id];
            $slot = collect($slotMetrics['slots'])->firstWhere('start', $time);
            $slotStart = $selectedDate->copy()->setTimeFromTimeString($time);

            if (! $slot || $slotStart->lessThanOrEqualTo(Carbon::now(config('app.timezone')))) {
                throw ValidationException::withMessages([
                    'scheduled_time' => 'Ce créneau n’est plus disponible. Veuillez relancer la recherche.',
                ]);
            }

            if ($slot['reserved'] >= $slot['capacity']) {
                throw ValidationException::withMessages([
                    'scheduled_time' => 'Ce créneau vient d’être réservé. Veuillez choisir un autre créneau.',
                ]);
            }

            if ($existingPickup
                && $existingPickup->pickup_point_id === $point->id
                && $existingPickup->scheduled_date->format('Y-m-d') === $selectedDate->format('Y-m-d')
                && substr((string) $existingPickup->scheduled_time, 0, 5) === $time) {
                return ['delivery' => $existingPickup, 'changed' => false];
            }

            $delivery = $existingPickup ?? new Delivery();
            $delivery->fill([
                'type' => 'pickup',
                'scheduled_date' => $selectedDate->format('Y-m-d'),
                'scheduled_time' => $time,
                'status' => 'planned',
                'delivery_fee' => $existingPickup ? $existingPickup->delivery_fee : $deliveryFee,
                'rental_id' => $lockedRental->id,
                'pickup_point_id' => $point->id,
            ]);
            $delivery->save();

            $lockedRental->renter?->notify(new PickupBookingConfirmed($delivery, $point));

            return ['delivery' => $delivery, 'changed' => true];
        });
    }

    public function saveHomeDelivery(
        Rental $rental,
        array $attributes,
        ?Delivery $delivery = null
    ): Delivery {
        return DB::transaction(function () use ($rental, $attributes, $delivery): Delivery {
            $lockedRental = Rental::query()
                ->whereKey($rental->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedRental->status, ['returned', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'rental_id' => 'Cette location ne peut plus recevoir de livraison.',
                ]);
            }

            if ($delivery) {
                $delivery = Delivery::query()
                    ->whereKey($delivery->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($delivery->rental_id !== $lockedRental->id || $delivery->status !== 'planned') {
                    throw ValidationException::withMessages([
                        'delivery' => 'Seule une livraison planifiée de cette location peut être modifiée.',
                    ]);
                }
            }

            $activeExists = Delivery::query()
                ->where('rental_id', $lockedRental->id)
                ->whereIn('status', ['planned', 'in_transit', 'delivered'])
                ->when($delivery, fn ($query) => $query->where('id', '!=', $delivery->id))
                ->lockForUpdate()
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'delivery' => 'Une livraison active existe déjà pour cette location.',
                ]);
            }

            $delivery ??= new Delivery();
            $delivery->fill([
                ...$attributes,
                'type' => 'home_delivery',
                'status' => 'planned',
                'pickup_point_id' => null,
                'rental_id' => $lockedRental->id,
            ]);
            $delivery->save();

            return $delivery;
        });
    }
}
