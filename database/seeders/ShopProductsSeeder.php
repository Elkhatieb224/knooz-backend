<?php

namespace Database\Seeders;

use App\Models\ShopProduct;
use Illuminate\Database\Seeder;

class ShopProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name_ar' => 'إطار ميشلان 18',
                'slug' => 'michelin-18',
                'category' => 'tires',
                'brand' => 'Michelin',
                'size' => '18',
                'price' => 450,
            ],
            [
                'name_ar' => 'إطار بريدجستون 17',
                'slug' => 'bridgestone-17',
                'category' => 'tires',
                'brand' => 'Bridgestone',
                'size' => '17',
                'price' => 380,
            ],
            [
                'name_ar' => 'زيت موبيل 5W-30',
                'slug' => 'mobil-5w30',
                'category' => 'oils',
                'brand' => 'Mobil',
                'size' => '4L',
                'price' => 145,
            ],
            [
                'name_ar' => 'زيت كاسترول 5W-40',
                'slug' => 'castrol-5w40',
                'category' => 'oils',
                'brand' => 'Castrol',
                'size' => '4L',
                'price' => 160,
            ],
        ];

        foreach ($products as $product) {
            ShopProduct::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    ...$product,
                    'stock' => 25,
                    'is_active' => true,
                    'description' => $product['name_ar'],
                ]
            );
        }

        $this->command?->info('Shop catalog products: '.count($products));
    }
}
