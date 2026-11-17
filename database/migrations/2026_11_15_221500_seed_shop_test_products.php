<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        $now = now();
        $rows = [
            [
                'name_ar' => 'اختبار اطارات مثال',
                'name_en' => 'Test Tires Example',
                'slug' => 'test-tires-example',
                'category' => 'tires',
                'brand' => 'اختبار',
                'size' => '18',
                'description' => 'منتج تجريبي للإطارات — يمكن التحكم به من لوحة التحكم.',
                'price' => 199,
                'stock' => 40,
                'is_active' => true,
                'meta' => json_encode(['badge' => 'اختبار', 'type' => 'تجريبي'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'name_ar' => 'اختبار اطارات مثال 17',
                'name_en' => 'Test Tires Example 17',
                'slug' => 'test-tires-example-17',
                'category' => 'tires',
                'brand' => 'اختبار',
                'size' => '17',
                'description' => 'منتج تجريبي مقاس 17 — للتحقق من فلاتر المتجر.',
                'price' => 175,
                'stock' => 30,
                'is_active' => true,
                'meta' => json_encode(['badge' => 'اختبار'], JSON_UNESCAPED_UNICODE),
            ],
            [
                'name_ar' => 'اختبار زيت مثال',
                'name_en' => 'Test Oil Example',
                'slug' => 'test-oil-example',
                'category' => 'oils',
                'brand' => 'اختبار',
                'size' => '4L',
                'description' => 'منتج تجريبي للزيوت — يمكن التحكم به من لوحة التحكم.',
                'price' => 89,
                'stock' => 50,
                'is_active' => true,
                'meta' => json_encode(['type' => 'زيت صناعي', 'badge' => 'اختبار'], JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('shop_products')->where('slug', $row['slug'])->exists();
            if ($exists) {
                DB::table('shop_products')->where('slug', $row['slug'])->update([
                    ...$row,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('shop_products')->insert([
                    ...$row,
                    'image_path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_products')) {
            return;
        }

        DB::table('shop_products')->whereIn('slug', [
            'test-tires-example',
            'test-tires-example-17',
            'test-oil-example',
        ])->delete();
    }
};
