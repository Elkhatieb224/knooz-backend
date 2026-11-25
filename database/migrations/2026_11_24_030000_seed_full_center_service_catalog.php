<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Full "حجز بالمركز" catalog (Figma center services list).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'name_ar' => 'وقود',
                'name_en' => 'Fuel',
                'slug' => 'fuel-center',
                'icon' => 'fuel',
                'description' => 'خدمة تعبئة الوقود',
                'base_price' => 0,
                'channel' => 'center',
                'kind_key' => 'fuel',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 10,
            ],
            [
                'name_ar' => 'الإطارات',
                'name_en' => 'Tires',
                'slug' => 'tires',
                'icon' => 'circle',
                'description' => 'بيع وتركيب الإطارات',
                'base_price' => 200,
                'channel' => 'center',
                'kind_key' => 'tires',
                'requires' => [],
                'flow_hint' => 'shop',
                'sort_order' => 20,
            ],
            [
                'name_ar' => 'الغسيل',
                'name_en' => 'Car Wash',
                'slug' => 'car-wash-center',
                'icon' => 'droplet',
                'description' => 'غسيل وتنظيف السيارة',
                'base_price' => 50,
                'channel' => 'center',
                'kind_key' => 'wash',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 30,
            ],
            [
                'name_ar' => 'زيوت وفلاتر',
                'name_en' => 'Oils & Filters',
                'slug' => 'oil-change-center',
                'icon' => 'oil',
                'description' => 'تغيير زيوت المحرك والفلاتر بأفضل الماركات',
                'base_price' => 120,
                'channel' => 'center',
                'kind_key' => 'oil',
                'requires' => [],
                'flow_hint' => 'shop',
                'sort_order' => 40,
            ],
            [
                'name_ar' => 'زينة سيارات',
                'name_en' => 'Car Accessories',
                'slug' => 'accessories-center',
                'icon' => 'seat',
                'description' => 'إكسسوارات وزينة',
                'base_price' => 0,
                'channel' => 'center',
                'kind_key' => 'accessories',
                'requires' => [],
                'flow_hint' => 'shop',
                'sort_order' => 50,
            ],
            [
                'name_ar' => 'ميكانيكي',
                'name_en' => 'Mechanic',
                'slug' => 'mechanic-center',
                'icon' => 'tools',
                'description' => 'صيانة ميكانيكية',
                'base_price' => 150,
                'channel' => 'center',
                'kind_key' => 'mechanic',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 60,
            ],
            [
                'name_ar' => 'سمكرة',
                'name_en' => 'Bodywork',
                'slug' => 'bodywork-center',
                'icon' => 'lift',
                'description' => 'إصلاح السمكرة',
                'base_price' => 300,
                'channel' => 'center',
                'kind_key' => 'bodywork',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 70,
            ],
            [
                'name_ar' => 'صبغ',
                'name_en' => 'Painting',
                'slug' => 'paint-center',
                'icon' => 'spray',
                'description' => 'صبغ ودهان',
                'base_price' => 400,
                'channel' => 'center',
                'kind_key' => 'paint',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 80,
            ],
            [
                'name_ar' => 'قطع غيار',
                'name_en' => 'Spare Parts',
                'slug' => 'parts-center',
                'icon' => 'parts',
                'description' => 'قطع غيار أصلية',
                'base_price' => 0,
                'channel' => 'center',
                'kind_key' => 'parts',
                'requires' => [],
                'flow_hint' => 'shop',
                'sort_order' => 90,
            ],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('services')->where('slug', $row['slug'])->first();
            $payload = [
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'],
                'icon' => $row['icon'],
                'description' => $row['description'],
                'base_price' => $row['base_price'],
                'channel' => $row['channel'],
                'kind_key' => $row['kind_key'],
                'requires' => json_encode($row['requires'], JSON_UNESCAPED_UNICODE),
                'flow_hint' => $row['flow_hint'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('services')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('services')->insert([
                    ...$payload,
                    'slug' => $row['slug'],
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep catalog rows.
    }
};
