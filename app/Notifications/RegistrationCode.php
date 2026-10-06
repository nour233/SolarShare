<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification SolarShare')
            ->greeting('Bienvenue sur SolarShare !')
            ->line('Saisissez ce code sur la page de vérification pour créer votre compte :')
            ->line('**'.$this->code.'**')
            ->line('Ce code expire dans 10 minutes. Ne le partagez avec personne.')
            ->line('Si vous n’avez pas demandé cette inscription, ignorez cet e-mail.')
            ->salutation('L’équipe SolarShare');
    }
}
