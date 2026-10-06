<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'amount',
        'method',
        'status',
        'type',
        'rental_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'  => 'En attente',
            'paid'     => 'Payé',
            'refunded' => 'Remboursé',
            default    => $this->status,
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'card'     => 'Carte',
            'cash'     => 'Espèces',
            'transfer' => 'Virement',
            default    => $this->method,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'rental'  => 'Location',
            'deposit' => 'Caution',
            default   => $this->type,
        };
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
