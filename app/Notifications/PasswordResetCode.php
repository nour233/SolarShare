<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Votre code de réinitialisation SolarShare')
            ->greeting('Bonjour !')->line('Pour choisir votre nouveau mot de passe, saisissez ce code sur SolarShare :')
            ->line('**'.$this->code.'**')->line('Ce code est valable 10 minutes et utilisable une seule fois. Ne le partagez pas.')
            ->line('Si vous n’avez pas demandé de réinitialisation, ignorez cet e-mail.')
            ->salutation('L’équipe SolarShare');
    }
}
