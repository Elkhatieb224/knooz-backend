<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'is_active',
        'description',
        'base_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'base_price' => 'decimal:2',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar;
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_service')
            ->withPivot(['contract_price', 'app_profit', 'is_available'])
            ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
