<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['user_id', 'rating', 'title', 'comment'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canBeModifiedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->created_at->gt(now()->subMinutes(5));
    }

    public function remainingEditSeconds(): int
    {
        return max(0, $this->created_at->addMinutes(5)->diffInSeconds(now(), false) * -1);
    }
}
