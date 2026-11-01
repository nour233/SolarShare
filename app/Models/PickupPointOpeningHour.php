<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupPointOpeningHour extends Model
{
    protected $fillable = [
        'day_of_week',
        'opens_at',
        'closes_at',
    ];

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class);
    }
}
