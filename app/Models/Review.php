<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const SERVICES = [
        'equipment' => 'Équipement',
        'rental' => 'Location',
        'maintenance' => 'Maintenance',
        'delivery' => 'Livraison',
        'general' => 'Avis général',
    ];

    protected $fillable = ['user_id', 'service', 'rating', 'title', 'comment'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canBeModifiedBy(User $user): bool
    {
        return $this->belongsToUser($user)
            && $this->created_at->gt(now()->subMinutes(5));
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->belongsToUser($user);
    }

    public function serviceLabel(): string
    {
        return self::SERVICES[$this->service] ?? 'Avis général';
    }

    public function remainingEditSeconds(): int
    {
        return max(0, $this->created_at->addMinutes(5)->diffInSeconds(now(), false) * -1);
    }

    private function belongsToUser(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
}
