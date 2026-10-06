<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamation extends Model
{
    public const TYPES = [
        'equipement' => 'Équipement',
        'location' => 'Location',
        'maintenance' => 'Maintenance',
        'livraison' => 'Livraison',
    ];

    public const STATUSES = [
        'ouverte' => 'Ouverte',
        'en_cours' => 'En cours',
        'resolue' => 'Résolue',
        'rejetee' => 'Rejetée',
    ];

    protected $fillable = ['user_id', 'type', 'subject', 'description', 'reclamation_date', 'status'];

    protected $casts = ['reclamation_date' => 'date'];

    protected $attributes = ['status' => 'ouverte'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
