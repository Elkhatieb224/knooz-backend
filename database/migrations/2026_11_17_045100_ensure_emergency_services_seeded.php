<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'name_ar' => 'طوارئ كفرات',
                'name_en' => 'Tire Emergency',
                'slug' => 'tire-emergency',
                'icon' => 'alert',
                'description' => 'خدمة طوارئ إصلاح أو تغيير الكفرات على الطريق',
                'base_price' => 250,
                'channel' => 'emergency',
                'kind_key' => 'tire',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'tire_repair',
                'sort_order' => 10,
            ],
            [
                'name_ar' => 'وقود طوارئ',
                'name_en' => 'Emergency Fuel',
                'slug' => 'fuel-emergency',
                'icon' => 'fuel',
                'description' => 'توصيل وقود إلى موقعك على الطريق',
                'base_price' => 80,
                'channel' => 'emergency',
                'kind_key' => 'fuel',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'fuel',
                'sort_order' => 20,
            ],
            [
                'name_ar' => 'سطحة طوارئ',
                'name_en' => 'Tow Truck',
                'slug' => 'tow-emergency',
                'icon' => 'tow',
                'description' => 'طلب سطحة إلى موقعك مع تحديد وجهة النقل',
                'base_price' => 150,
                'channel' => 'emergency',
                'kind_key' => 'tow',
                'requires' => ['location', 'destination', 'notes'],
                'flow_hint' => 'tow',
                'sort_order' => 30,
            ],
            [
                'name_ar' => 'شحن بطارية',
                'name_en' => 'Battery Jump Start',
                'slug' => 'battery-jump',
                'icon' => 'battery',
                'description' => 'تشغيل السيارة عند نفاد البطارية في موقعك',
                'base_price' => 90,
                'channel' => 'emergency',
                'kind_key' => 'battery',
                'requires' => ['location', 'vehicle', 'notes'],
                'flow_hint' => 'generic_emergency',
                'sort_order' => 40,
            ],
            [
                'name_ar' => 'فتح سيارة مقفلة',
                'name_en' => 'Lockout Service',
                'slug' => 'lockout',
                'icon' => 'key',
                'description' => 'فتح السيارة عند نسيان المفاتيح داخلها',
                'base_price' => 110,
                'channel' => 'emergency',
                'kind_key' => 'lockout',
                'requires' => ['location', 'vehicle', 'plate', 'notes'],
                'flow_hint' => 'generic_emergency',
                'sort_order' => 50,
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
        // Keep services; visibility is controlled via is_active.
    }
};
