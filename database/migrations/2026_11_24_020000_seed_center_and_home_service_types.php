<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures branch (center) and home-visit service types exist for admin → center assignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'name_ar' => 'غسيل في المركز',
                'name_en' => 'Branch Car Wash',
                'slug' => 'car-wash-center',
                'icon' => 'droplet',
                'description' => 'غسيل السيارات داخل فرع المركز',
                'base_price' => 50,
                'channel' => 'center',
                'kind_key' => 'wash',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 10,
            ],
            [
                'name_ar' => 'تغيير زيت في المركز',
                'name_en' => 'Branch Oil Change',
                'slug' => 'oil-change-center',
                'icon' => 'oil',
                'description' => 'تغيير الزيت والفلاتر داخل فرع المركز',
                'base_price' => 120,
                'channel' => 'center',
                'kind_key' => 'oil',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 20,
            ],
            [
                'name_ar' => 'كفرات في المركز',
                'name_en' => 'Branch Tires',
                'slug' => 'tires',
                'icon' => 'circle',
                'description' => 'صيانة وتغيير الإطارات داخل الفرع',
                'base_price' => 200,
                'channel' => 'center',
                'kind_key' => 'tires',
                'requires' => [],
                'flow_hint' => 'branch',
                'sort_order' => 30,
            ],
            [
                'name_ar' => 'غسيل عند المنزل',
                'name_en' => 'Home Car Wash',
                'slug' => 'car-wash',
                'icon' => 'droplet',
                'description' => 'غسيل السيارات عند موقع العميل',
                'base_price' => 70,
                'channel' => 'home',
                'kind_key' => 'wash',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'home_visit',
                'sort_order' => 10,
            ],
            [
                'name_ar' => 'تغيير زيت عند المنزل',
                'name_en' => 'Home Oil Change',
                'slug' => 'oil-change',
                'icon' => 'oil',
                'description' => 'تغيير الزيت عند موقع العميل',
                'base_price' => 140,
                'channel' => 'home',
                'kind_key' => 'oil',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'home_visit',
                'sort_order' => 20,
            ],
            [
                'name_ar' => 'كفرات عند المنزل',
                'name_en' => 'Home Tires',
                'slug' => 'tires-home',
                'icon' => 'circle',
                'description' => 'خدمة إطارات عند موقع العميل',
                'base_price' => 220,
                'channel' => 'home',
                'kind_key' => 'tires',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'home_visit',
                'sort_order' => 30,
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
        // Keep catalog rows; visibility is via is_active / center assignment.
    }
};
