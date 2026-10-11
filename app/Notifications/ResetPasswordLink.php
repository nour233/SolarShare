<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordLink extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        return (new MailMessage)->subject('Réinitialisez votre mot de passe SolarShare')
            ->greeting('Bonjour !')->line('Vous avez demandé un nouveau mot de passe pour votre compte SolarShare.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line('Ce lien est valable '.config('auth.passwords.users.expire').' minutes et ne peut être utilisé qu’une fois.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail. Votre mot de passe reste inchangé.')
            ->salutation('L’équipe SolarShare');
    }
}
