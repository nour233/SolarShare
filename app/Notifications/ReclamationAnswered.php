<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReclamationAnswered extends Notification
{
    use Queueable;

    public function __construct(public Reclamation $reclamation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabels = [
            'en_attente' => 'est en cours d\'examen',
            'en_cours'   => 'est prise en charge',
            'traitee'    => 'a été traitée',
            'rejetee'    => 'a été rejetée',
        ];

        $statusIcons = [
            'traitee'  => ['icon' => 'fa-check-circle', 'color' => 'text-success'],
            'rejetee'  => ['icon' => 'fa-times-circle', 'color' => 'text-danger'],
            'en_cours' => ['icon' => 'fa-spinner', 'color' => 'text-primary'],
        ];

        $style = $statusIcons[$this->reclamation->status] ?? ['icon' => 'fa-comment-alt', 'color' => 'text-info'];
        $label = $statusLabels[$this->reclamation->status] ?? $this->reclamation->status;

        return [
            'type'           => 'reclamation_answered',
            'icon'           => $style['icon'],
            'color'          => $style['color'],
            'title'          => 'Réclamation mise à jour',
            'message'        => 'Votre réclamation "' . ($this->reclamation->subject ?? 'Sans objet') . '" ' . $label . '.',
            'reclamation_id' => $this->reclamation->id,
            'status'         => $this->reclamation->status,
            'url'            => route('reclamations.show', $this->reclamation->id),
            'action_text'    => 'Consulter la réponse',
        ];
    }
}

