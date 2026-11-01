<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogisticsPreference extends Model
{
    protected $fillable = [
        'preferred_pickup_point_id',
        'preferred_start_time',
        'preferred_end_time',
        'preferred_city',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredPickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class, 'preferred_pickup_point_id');
    }
}
