<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopProduct extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'category',
        'brand',
        'size',
        'description',
        'price',
        'image_path',
        'stock',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }
}
