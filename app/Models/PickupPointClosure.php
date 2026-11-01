<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupPointClosure extends Model
{
    protected $fillable = [
        'closure_date',
        'reason',
    ];

    protected $casts = [
        'closure_date' => 'date',
    ];

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class);
    }
}
