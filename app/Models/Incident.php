<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    public const SEVERITIES = ['mineur' => 'Mineur', 'moyen' => 'Moyen', 'grave' => 'Grave'];

    public const STATUSES = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'resolu' => 'Résolu', 'rejete' => 'Rejeté'];

    protected $fillable = ['equipment_id', 'user_id', 'rental_id', 'description', 'severity', 'photos', 'status', 'ai_diagnosis', 'repair_estimate'];

    protected $attributes = ['status' => 'ouvert'];

    protected function casts(): array
    {
        return ['photos' => 'array', 'ai_diagnosis' => 'array', 'repair_estimate' => 'array'];
    }

    /** Date the equipment should be available again, from the ML estimate. */
    public function estimatedReadyOn(): ?\Illuminate\Support\Carbon
    {
        return isset($this->repair_estimate['days']) ? $this->created_at->copy()->addDays($this->repair_estimate['days']) : null;
    }

    /** True when the diagnosis thinks the reporter under-estimated the problem. */
    public function severityUnderestimated(): bool
    {
        $order = array_flip(array_keys(self::SEVERITIES));
        $suggested = $this->ai_diagnosis['severity'] ?? null;

        return $suggested !== null && ($order[$suggested] ?? 0) > ($order[$this->severity] ?? 0);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IncidentMessage::class)->orderBy('id');
    }

    public function isReporter(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    /** Resolved or rejected incidents keep their conversation read-only. */
    public function isClosed(): bool
    {
        return in_array($this->status, ['resolu', 'rejete'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function severityLabel(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    /**
     * Messages the viewer has not read yet: the reporter reads what the admins
     * wrote, the admins read what the reporter wrote.
     */
    public function unreadMessagesFor(User $viewer): HasMany
    {
        $query = $this->messages()->whereNull('read_at')->where('user_id', '!=', $viewer->id);

        return $this->isReporter($viewer) ? $query : $query->where('user_id', $this->user_id);
    }

    public function markReadFor(User $viewer): void
    {
        $this->unreadMessagesFor($viewer)->update(['read_at' => now()]);
    }
}
