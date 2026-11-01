<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\User;
use App\Notifications\DeliveryStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryStatusService
{
    private const TRANSITIONS = [
        'planned' => ['in_transit'],
        'in_transit' => ['delivered'],
        'delivered' => ['returned'],
        'returned' => [],
    ];

    public function allowedNextStatuses(Delivery $delivery): array
    {
        return self::TRANSITIONS[$delivery->status] ?? [];
    }

    public function transition(
        Delivery $delivery,
        string $newStatus,
        ?User $actor = null,
        ?string $comment = null
    ): bool {
        $history = DB::transaction(function () use ($delivery, $newStatus, $actor, $comment) {
            $lockedDelivery = Delivery::query()
                ->whereKey($delivery->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDelivery->status === $newStatus) {
                return null;
            }

            if (! in_array($newStatus, $this->allowedNextStatuses($lockedDelivery), true)) {
                throw ValidationException::withMessages([
                    'status' => 'Cette transition de statut n’est pas autorisée.',
                ]);
            }

            $previousStatus = $lockedDelivery->status;
            $lockedDelivery->update(['status' => $newStatus]);

            $history = $lockedDelivery->statusHistories()->create([
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'user_id' => $actor?->getKey(),
                'comment' => $comment,
            ]);

            $renter = $lockedDelivery->rental()->with('renter')->first()?->renter;
            $renter?->notify(new DeliveryStatusUpdated($lockedDelivery, $history));

            return $history;
        });

        return $history instanceof DeliveryStatusHistory;
    }
}
