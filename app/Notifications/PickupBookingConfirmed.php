<?php

namespace App\Notifications;

use App\Models\Delivery;
use App\Models\PickupPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PickupBookingConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        public Delivery $delivery,
        public PickupPoint $pickupPoint
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->delivery->loadMissing('rental.equipment');

        return [
            'type' => 'pickup_booking',
            'icon' => 'fa-map-marker-alt',
            'color' => 'text-success',
            'title' => 'Retrait confirmé',
            'message' => 'Retrait de "'.($this->delivery->rental->equipment?->title ?? 'votre équipement')
                .'" confirmé au '.$this->pickupPoint->name.' le '
                .$this->delivery->scheduled_date->format('d/m/Y').' à '
                .substr((string) $this->delivery->scheduled_time, 0, 5).'.',
            'delivery_id' => $this->delivery->id,
            'rental_id' => $this->delivery->rental_id,
            'pickup_point_id' => $this->pickupPoint->id,
            'scheduled_date' => $this->delivery->scheduled_date->format('Y-m-d'),
            'scheduled_time' => substr((string) $this->delivery->scheduled_time, 0, 5),
            'url' => route('rentals.show', $this->delivery->rental_id),
            'action_text' => 'Consulter ma réservation',
        ];
    }
}
