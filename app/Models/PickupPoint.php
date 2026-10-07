<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupPoint extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'latitude',
        'longitude',
        'opening_hours',
        'capacity',
        'status',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}