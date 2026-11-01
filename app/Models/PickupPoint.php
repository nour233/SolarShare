<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupPoint extends Model
{
    protected $attributes = [
        'slot_duration_minutes' => 30,
    ];

    protected $fillable = [
        'name',
        'address',
        'city',
        'latitude',
        'longitude',
        'opening_hours',
        'capacity',
        'slot_duration_minutes',
        'status',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(PickupPointOpeningHour::class)
            ->orderBy('day_of_week')
            ->orderBy('opens_at');
    }

    public function closures(): HasMany
    {
        return $this->hasMany(PickupPointClosure::class);
    }
}