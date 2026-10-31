<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Rental $rental,
        public string $newStatus,
        public ?string $oldStatus = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->rental->loadMissing('equipment');

        $statusLabels = [
            'pending'   => 'mise en attente',
            'accepted'  => 'acceptée',
            'ongoing'   => 'en cours',
            'returned'  => 'marquée comme retournée',
            'cancelled' => 'annulée',
        ];

        $statusIcons = [
            'accepted'  => ['icon' => 'fa-check-circle', 'color' => 'text-success'],
            'cancelled' => ['icon' => 'fa-times-circle', 'color' => 'text-danger'],
            'ongoing'   => ['icon' => 'fa-clock', 'color' => 'text-primary'],
            'returned'  => ['icon' => 'fa-undo', 'color' => 'text-info'],
            'pending'   => ['icon' => 'fa-hourglass-half', 'color' => 'text-warning'],
        ];

        $style = $statusIcons[$this->newStatus] ?? ['icon' => 'fa-info-circle', 'color' => 'text-secondary'];
        $label = $statusLabels[$this->newStatus] ?? $this->newStatus;
        $equipmentTitle = $this->rental->equipment?->title ?? 'votre équipement';

        return [
            'type'        => 'rental_status',
            'icon'        => $style['icon'],
            'color'       => $style['color'],
            'title'       => 'Statut de réservation mis à jour',
            'message'     => 'Votre réservation pour "' . $equipmentTitle . '" a été ' . $label . '.',
            'rental_id'   => $this->rental->id,
            'status'      => $this->newStatus,
            'url'         => route('rentals.show', $this->rental->id),
            'action_text' => 'Consulter ma réservation',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->rental->loadMissing('equipment');
        $label = $this->rental->statusLabel();
        $equipmentTitle = $this->rental->equipment?->title ?? 'votre équipement';

        $mail = (new MailMessage)
            ->subject('Mise à jour de votre réservation #' . str_pad($this->rental->id, 4, '0', STR_PAD_LEFT) . ' – SolarShare')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Le statut de votre réservation pour **' . $equipmentTitle . '** a changé : **' . $label . '**.')
            ->line('Dates : du ' . $this->rental->start_date?->format('d/m/Y') . ' au ' . $this->rental->end_date?->format('d/m/Y'))
            ->line('Montant total : ' . number_format($this->rental->total_price, 2, ',', ' ') . ' €')
            ->action('Voir ma réservation', route('rentals.show', $this->rental->id))
            ->line('Merci pour votre confiance sur SolarShare !');

        return $mail;
    }
}

