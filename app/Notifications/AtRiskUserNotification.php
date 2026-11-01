<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AtRiskUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $segment,
        private array $data,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isLost = $this->segment === 'lost';

        $title = $isLost
            ? 'Vous nous manquez ! 😢'
            : 'Ça fait un moment ! ☀️';

        $message = $isLost
            ? 'Nous n\'avons pas eu de vos nouvelles depuis longtemps. Profitez de nos équipements solaires avec une offre spéciale de retour !'
            : 'Votre dernière location remonte à un moment. Découvrez nos nouveaux équipements solaires et bénéficiez d\'avantages fidélité !';

        $daysSince = $this->data['details']['days_since_last'] ?? null;
        if ($daysSince) {
            $message .= " (Dernière activité il y a {$daysSince} jours)";
        }

        return [
            'title' => $title,
            'message' => $message,
            'type' => 'rfm_reengagement',
            'segment' => $this->segment,
            'icon' => $isLost ? '💔' : '⚠️',
            'url' => route('equipments.index'),
        ];
    }
}

