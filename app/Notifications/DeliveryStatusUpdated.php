<?php

namespace App\Notifications;

use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Delivery $delivery,
        public DeliveryStatusHistory $history
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $labels = [
            'planned' => 'Planifiée',
            'in_transit' => 'En transit',
            'delivered' => 'Livrée',
            'returned' => 'Retournée',
        ];

        $statusLabel = $labels[$this->history->new_status] ?? $this->history->new_status;
        $equipmentTitle = $this->delivery->rental?->equipment?->title ?? 'votre équipement';

        return [
            'type' => 'delivery_status',
            'icon' => 'fa-truck',
            'color' => 'text-primary',
            'title' => 'Statut de livraison mis à jour',
            'message' => 'La livraison de "'.$equipmentTitle.'" est maintenant : '.$statusLabel.'.',
            'delivery_id' => $this->delivery->id,
            'status' => $this->history->new_status,
            'url' => route('rentals.show', $this->delivery->rental_id),
            'action_text' => 'Consulter ma location',
        ];
    }
}
