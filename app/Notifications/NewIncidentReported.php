<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewIncidentReported extends Notification
{
    use Queueable;

    public function __construct(public Incident $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->incident->loadMissing(['user', 'equipment']);

        return [
            'type'        => 'new_incident',
            'icon'        => 'fa-exclamation-triangle',
            'color'       => 'text-danger',
            'title'       => 'Nouvel incident signalé',
            'message'     => ($this->incident->user?->name ?? 'Un utilisateur') . ' : "' . ($this->incident->title ?? 'Incident') . '"',
            'incident_id' => $this->incident->id,
            'url'         => route('admin.incidents.show', $this->incident->id),
            'action_text' => 'Consulter l\'incident',
        ];
    }
}

