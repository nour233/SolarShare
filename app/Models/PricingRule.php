<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PricingRule extends Model
{
    protected $fillable = ['key', 'label', 'group', 'value', 'unit', 'description'];

    protected function casts(): array
    {
        return ['value' => 'decimal:4'];
    }

    /** Flush the pricing cache whenever a rule is saved or deleted. */
    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('pricing_rules');
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Load all rules as a keyed collection, cached for 1 hour.
     * Returns array<string, float>
     */
    public static function allCached(): array
    {
        return Cache::remember('pricing_rules', 3600, function () {
            return static::pluck('value', 'key')->map(fn ($v) => (float) $v)->all();
        });
    }

    public function groupLabel(): string
    {
        return match ($this->group) {
            'weekend'  => 'Week-end',
            'peak'     => 'Haute saison',
            'discount' => 'Réductions',
            default    => $this->group,
        };
    }
}
