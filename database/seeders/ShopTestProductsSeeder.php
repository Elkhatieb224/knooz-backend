<?php

namespace Database\Seeders;

use App\Models\ShopProduct;
use Illuminate\Database\Seeder;

/**
 * Test catalog products controllable from admin panel (brand: اختبار).
 */
class ShopTestProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name_ar' => 'اختبار اطارات مثال',
                'name_en' => 'Test Tires Example',
                'slug' => 'test-tires-example',
                'category' => 'tires',
                'brand' => 'اختبار',
                'size' => '18',
                'price' => 199,
                'stock' => 40,
                'description' => 'منتج تجريبي للإطارات — يمكن التحكم به من لوحة التحكم.',
                'meta' => ['badge' => 'اختبار', 'type' => 'تجريبي'],
            ],
            [
                'name_ar' => 'اختبار اطارات مثال 17',
                'name_en' => 'Test Tires Example 17',
                'slug' => 'test-tires-example-17',
                'category' => 'tires',
                'brand' => 'اختبار',
                'size' => '17',
                'price' => 175,
                'stock' => 30,
                'description' => 'منتج تجريبي مقاس 17 — للتحقق من فلاتر المتجر.',
                'meta' => ['badge' => 'اختبار'],
            ],
            [
                'name_ar' => 'اختبار زيت مثال',
                'name_en' => 'Test Oil Example',
                'slug' => 'test-oil-example',
                'category' => 'oils',
                'brand' => 'اختبار',
                'size' => '4L',
                'price' => 89,
                'stock' => 50,
                'description' => 'منتج تجريبي للزيوت — يمكن التحكم به من لوحة التحكم.',
                'meta' => ['type' => 'زيت صناعي', 'badge' => 'اختبار'],
            ],
        ];

        foreach ($products as $product) {
            ShopProduct::query()->updateOrCreate(
                ['slug' => $product['slug']],
                [
                    ...$product,
                    'is_active' => true,
                ]
            );
        }
    }
}
