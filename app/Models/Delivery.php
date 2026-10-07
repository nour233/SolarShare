<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}