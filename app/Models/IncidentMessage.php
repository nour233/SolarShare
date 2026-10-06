<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentMessage extends Model
{
    protected $fillable = ['incident_id', 'user_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Total unread messages for the navbar / sidebar badge. */
    public static function unreadCountFor(User $user): int
    {
        $query = self::whereNull('read_at')->where('user_id', '!=', $user->id);

        if ($user->isAdmin()) {
            // Admins read what reporters wrote, in every incident.
            return $query->whereHas('incident', fn (Builder $incident) => $incident->whereColumn('incidents.user_id', 'incident_messages.user_id'))->count();
        }

        return $query->whereHas('incident', fn (Builder $incident) => $incident->where('user_id', $user->id))->count();
    }

    /** Shape sent to the chat script. */
    public function toChatArray(User $viewer): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author' => $this->user->name,
            'time' => $this->created_at->format('d/m/Y H:i'),
            'mine' => (int) $this->user_id === (int) $viewer->id,
        ];
    }
}
