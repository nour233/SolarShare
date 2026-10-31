<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReclamationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Reclamation $reclamation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->reclamation->loadMissing('user');

        return [
            'type'           => 'new_reclamation',
            'icon'           => 'fa-comment-dots',
            'color'          => 'text-warning',
            'title'          => 'Nouvelle réclamation client',
            'message'        => ($this->reclamation->user?->name ?? 'Un client') . ' a soumis : "' . ($this->reclamation->subject ?? 'Réclamation') . '"',
            'reclamation_id' => $this->reclamation->id,
            'url'            => route('admin.reclamations.show', $this->reclamation->id),
            'action_text'    => 'Voir la réclamation',
        ];
    }
}

