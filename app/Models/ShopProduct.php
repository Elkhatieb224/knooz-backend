<?php

namespace App\Models;

use App\Support\CenterServiceCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopProduct extends Model
{
    protected $fillable = [
        'center_id',
        'name_ar',
        'name_en',
        'slug',
        'category',
        'brand',
        'size',
        'description',
        'price',
        'app_profit',
        'app_profit_mode',
        'image_path',
        'stock',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'app_profit' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function scopeOwnedByCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function scopePlatformCatalog(Builder $query): Builder
    {
        return $query->whereNull('center_id');
    }

    /**
     * True when admin set a product-specific commission (null = inherit center default).
     */
    public function hasCustomCommission(): bool
    {
        return $this->app_profit !== null;
    }

    public function resolvedAppProfit(?Center $center = null): ?float
    {
        if ($this->hasCustomCommission()) {
            return (float) $this->app_profit;
        }

        $owner = $center ?? $this->center;
        if ($owner && $owner->product_app_profit !== null) {
            return (float) $owner->product_app_profit;
        }

        return null;
    }

    public function resolvedAppProfitMode(?Center $center = null): string
    {
        if ($this->hasCustomCommission()) {
            return CenterServiceCommission::normalizeMode($this->app_profit_mode);
        }

        $owner = $center ?? $this->center;
        if ($owner) {
            return CenterServiceCommission::normalizeMode($owner->product_app_profit_mode);
        }

        return CenterServiceCommission::normalizeMode($this->app_profit_mode);
    }

    public function commissionSource(?Center $center = null): string
    {
        if ($this->hasCustomCommission()) {
            return 'custom';
        }

        $owner = $center ?? $this->center;
        if ($owner && $owner->product_app_profit !== null) {
            return 'center_default';
        }

        return 'none';
    }

    public function appCommissionAmount(?float $salePrice = null, ?Center $center = null): float
    {
        return CenterServiceCommission::commissionAmount(
            (float) ($salePrice ?? $this->price ?? 0),
            $this->resolvedAppProfit($center),
            $this->resolvedAppProfitMode($center),
        );
    }

    public function netForCenter(?float $salePrice = null, ?Center $center = null): float
    {
        $amount = (float) ($salePrice ?? $this->price ?? 0);

        return CenterServiceCommission::netForCenter(
            $amount,
            $this->resolvedAppProfit($center),
            $this->resolvedAppProfitMode($center),
        );
    }

    /**
     * @return array{
     *   app_commission: float,
     *   center_net: float,
     *   app_profit: float|null,
     *   app_profit_mode: string,
     *   commission_source: string,
     *   has_custom_commission: bool
     * }
     */
    public function profitBreakdown(?float $salePrice = null, ?Center $center = null): array
    {
        $price = (float) ($salePrice ?? $this->price ?? 0);
        $owner = $center ?? ($this->relationLoaded('center') ? $this->center : $this->center()->first());

        return [
            'app_commission' => $this->appCommissionAmount($price, $owner),
            'center_net' => $this->netForCenter($price, $owner),
            'app_profit' => $this->resolvedAppProfit($owner),
            'app_profit_mode' => $this->resolvedAppProfitMode($owner),
            'commission_source' => $this->commissionSource($owner),
            'has_custom_commission' => $this->hasCustomCommission(),
        ];
    }
}
