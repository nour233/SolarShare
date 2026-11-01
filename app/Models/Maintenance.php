<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    public const TYPES = ['nettoyage' => 'Nettoyage', 'reparation' => 'Réparation', 'inspection' => 'Inspection'];

    protected $fillable = ['equipment_id', 'incident_id', 'type', 'date', 'cost', 'duration_days', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date', 'cost' => 'decimal:2', 'duration_days' => 'integer'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
