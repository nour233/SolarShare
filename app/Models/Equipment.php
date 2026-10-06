<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Equipment extends Model
{
    protected $fillable = ['title','description','power_capacity','power_unit','condition','price_per_day','deposit','photos','category_id','owner_id'];
    protected function casts(): array { return ['photos'=>'array','power_capacity'=>'decimal:2','price_per_day'=>'decimal:2','deposit'=>'decimal:2']; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }

    public function rentals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Return date ranges that are already booked (active statuses only).
     * Returns a collection of objects with start_date and end_date.
     */
    public function bookedRanges(): \Illuminate\Support\Collection
    {
        return $this->rentals()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->get(['start_date', 'end_date']);
    }
}
