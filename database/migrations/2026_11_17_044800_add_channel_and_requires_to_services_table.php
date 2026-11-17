<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('channel', 32)->default('center')->after('slug')->index();
            $table->string('kind_key', 40)->nullable()->after('channel')->index();
            $table->json('requires')->nullable()->after('kind_key');
            $table->string('flow_hint', 40)->nullable()->after('requires');
            $table->unsignedInteger('sort_order')->default(100)->after('flow_hint');
        });

        // Backfill existing rows by slug.
        $map = [
            'car-wash' => ['channel' => 'home', 'kind_key' => 'wash', 'requires' => ['location', 'notes'], 'flow_hint' => 'home_visit', 'sort_order' => 10],
            'oil-change' => ['channel' => 'home', 'kind_key' => 'oil', 'requires' => ['location', 'notes'], 'flow_hint' => 'home_visit', 'sort_order' => 20],
            'tires' => ['channel' => 'center', 'kind_key' => 'tires', 'requires' => [], 'flow_hint' => 'branch', 'sort_order' => 30],
            'tire-emergency' => ['channel' => 'emergency', 'kind_key' => 'tire', 'requires' => ['location', 'notes'], 'flow_hint' => 'tire_repair', 'sort_order' => 10],
        ];

        foreach ($map as $slug => $attrs) {
            DB::table('services')->where('slug', $slug)->update([
                'channel' => $attrs['channel'],
                'kind_key' => $attrs['kind_key'],
                'requires' => json_encode($attrs['requires'], JSON_UNESCAPED_UNICODE),
                'flow_hint' => $attrs['flow_hint'],
                'sort_order' => $attrs['sort_order'],
                'updated_at' => now(),
            ]);
        }

        $emergencyExtras = [
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

        foreach ($emergencyExtras as $row) {
            $exists = DB::table('services')->where('slug', $row['slug'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('services')->insert([
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'],
                'slug' => $row['slug'],
                'icon' => $row['icon'],
                'description' => $row['description'],
                'base_price' => $row['base_price'],
                'is_active' => true,
                'channel' => $row['channel'],
                'kind_key' => $row['kind_key'],
                'requires' => json_encode($row['requires'], JSON_UNESCAPED_UNICODE),
                'flow_hint' => $row['flow_hint'],
                'sort_order' => $row['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['channel', 'kind_key', 'requires', 'flow_hint', 'sort_order']);
        });
    }
};
