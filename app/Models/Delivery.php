<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'type',
        'scheduled_date',
        'scheduled_time',
        'status',
        'delivery_fee',
        'rental_id',
        'pickup_point_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivery_fee' => 'decimal:2',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class)->orderBy('created_at');
    }

    public static function statusLabelFor(string $status): string
    {
        return [
            'planned' => 'Planifiée',
            'in_transit' => 'En transit',
            'delivered' => 'Livrée',
            'returned' => 'Retournée',
        ][$status] ?? $status;
    }

    public static function statusBadgeClass(string $status): string
    {
        return [
            'planned' => 'bg-secondary',
            'in_transit' => 'bg-primary',
            'delivered' => 'bg-success',
            'returned' => 'bg-dark',
        ][$status] ?? 'bg-secondary';
    }
}