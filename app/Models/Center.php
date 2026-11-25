<?php

namespace App\Models;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Support\ServiceChannels;
use Illuminate\Database\Eloquent\Builder;
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
        'offers_center_service',
        'offers_home_service',
        'offers_emergency_service',
        'show_revenue',
        'product_app_profit',
        'product_app_profit_mode',
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
            'offers_center_service' => 'boolean',
            'offers_home_service' => 'boolean',
            'offers_emergency_service' => 'boolean',
            'product_app_profit' => 'decimal:2',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function offersChannel(string $channel): bool
    {
        return match ($channel) {
            ServiceChannels::CENTER => (bool) $this->offers_center_service,
            ServiceChannels::HOME => (bool) $this->offers_home_service,
            ServiceChannels::EMERGENCY => (bool) $this->offers_emergency_service,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public function offeredChannels(): array
    {
        $channels = [];
        if ($this->offers_center_service) {
            $channels[] = ServiceChannels::CENTER;
        }
        if ($this->offers_home_service) {
            $channels[] = ServiceChannels::HOME;
        }
        if ($this->offers_emergency_service) {
            $channels[] = ServiceChannels::EMERGENCY;
        }

        return $channels;
    }

    public function scopeOfferingChannel(Builder $query, string $channel): Builder
    {
        $column = match ($channel) {
            ServiceChannels::CENTER => 'offers_center_service',
            ServiceChannels::HOME => 'offers_home_service',
            ServiceChannels::EMERGENCY => 'offers_emergency_service',
            default => null,
        };

        if ($column === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, true);
    }

    public function showsRevenue(): bool
    {
        if ($this->show_revenue !== null) {
            return (bool) $this->show_revenue;
        }

        // Contracted partners see net revenue after app commission by default.
        // Owned centers hide it unless admin enables the flag.
        return $this->type === CenterType::Contracted;
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
            ->withPivot(['contract_price', 'app_profit', 'app_profit_mode', 'is_available'])
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

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function canManageProducts(): bool
    {
        return (bool) $this->offers_center_service;
    }
}
