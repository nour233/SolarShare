<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'total_price',
        'status',
        'equipment_id',
        'renter_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'En attente',
            'accepted'  => 'Acceptée',
            'ongoing'   => 'En cours',
            'returned'  => 'Retournée',
            'cancelled' => 'Annulée',
            default     => $this->status,
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'pending'   => 'badge-pending',
            'accepted'  => 'badge-accepted',
            'ongoing'   => 'badge-ongoing',
            'returned'  => 'badge-returned',
            'cancelled' => 'badge-cancelled',
            default     => 'badge-secondary',
        };
    }

    public function durationDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Relationships
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // One rental can have multiple delivery records
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}