<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    public const TYPES = ['nettoyage' => 'Nettoyage', 'reparation' => 'Réparation', 'inspection' => 'Inspection'];

    protected $fillable = ['equipment_id', 'type', 'date', 'cost', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date', 'cost' => 'decimal:2'];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
