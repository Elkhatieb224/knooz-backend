<?php

namespace App\Models;

use App\Enums\PackageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'duration_days',
        'operations_count',
        'vehicles_count',
        'price',
        'discount_percent',
        'free_services',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PackageType::class,
            'price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'free_services' => 'array',
            'is_active' => 'boolean',
            'duration_days' => 'integer',
            'operations_count' => 'integer',
            'vehicles_count' => 'integer',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
