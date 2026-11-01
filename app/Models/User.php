<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rfm_segment',
        'rfm_recency_score',
        'rfm_frequency_score',
        'rfm_monetary_score',
        'rfm_total_score',
        'rfm_total_spent',
        'rfm_rental_count',
        'rfm_days_since_last',
        'rfm_segmented_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            'rfm_total_score' => 'decimal:2',
            'rfm_total_spent' => 'decimal:2',
            'rfm_segmented_at' => 'datetime',

            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',

        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Profile photo, or null to show the initials. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset($this->avatar) : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordLink($token));
    }

    public function equipments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Equipment::class, 'owner_id');
    }

    public function incidents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function rentals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rental::class, 'renter_id');
    }

    public function reclamations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }
}
