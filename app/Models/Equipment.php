<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Equipment extends Model
{
    protected $fillable = ['title','description','power_capacity','power_unit','condition','price_per_day','deposit','photos','category_id','owner_id'];
    protected function casts(): array { return ['photos'=>'array','power_capacity'=>'decimal:2','price_per_day'=>'decimal:2','deposit'=>'decimal:2']; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function maintenances(): HasMany { return $this->hasMany(Maintenance::class); }
    public function incidents(): HasMany { return $this->hasMany(Incident::class); }
}
