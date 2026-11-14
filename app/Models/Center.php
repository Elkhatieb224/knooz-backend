<?php

namespace App\Models;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Center extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'address',
        'city',
        'lat',
        'lng',
        'status',
        'show_revenue',
        'manager_id',
        'manager_center_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CenterType::class,
            'status' => CenterStatus::class,
            'show_revenue' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function showsRevenue(): bool
    {
        if ($this->show_revenue !== null) {
            return (bool) $this->show_revenue;
        }

        return $this->type === CenterType::Owned;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function managerCenterUser(): BelongsTo
    {
        return $this->belongsTo(CenterUser::class, 'manager_center_user_id');
    }

    public function centerUsers(): HasMany
    {
        return $this->hasMany(CenterUser::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'center_service')
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
}
