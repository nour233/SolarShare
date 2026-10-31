<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRentalRequested extends Notification
{
    use Queueable;

    public function __construct(public Rental $rental) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->rental->loadMissing(['renter', 'equipment']);

        return [
            'type'        => 'rental_requested',
            'icon'        => 'fa-calendar-plus',
            'color'       => 'text-warning',
            'title'       => 'Nouvelle demande de location',
            'message'     => ($this->rental->renter?->name ?? 'Un client') . ' a réservé "' . ($this->rental->equipment?->title ?? 'un équipement') . '"',
            'rental_id'   => $this->rental->id,
            'url'         => route('admin.rentals.show', $this->rental->id),
            'action_text' => 'Examiner la demande',
        ];
    }
}

