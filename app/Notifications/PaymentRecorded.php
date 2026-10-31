<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRecorded extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->payment->loadMissing(['rental.equipment']);

        $typeLabel = $this->payment->type === 'deposit' ? 'Caution' : 'Location';
        $statusLabel = match ($this->payment->status) {
            'paid'     => 'Payé',
            'pending'  => 'En attente',
            'refunded' => 'Remboursé',
            default    => $this->payment->status,
        };

        return [
            'type'        => 'payment_recorded',
            'icon'        => 'fa-receipt',
            'color'       => $this->payment->status === 'paid' ? 'text-success' : 'text-primary',
            'title'       => 'Paiement enregistré (' . $typeLabel . ')',
            'message'     => 'Un versement de ' . number_format($this->payment->amount, 2, ',', ' ') . ' € (' . $statusLabel . ') a été enregistré.',
            'payment_id'  => $this->payment->id,
            'rental_id'   => $this->payment->rental_id,
            'url'         => route('rentals.show', $this->payment->rental_id),
            'action_text' => 'Voir les détails',
        ];
    }
}

