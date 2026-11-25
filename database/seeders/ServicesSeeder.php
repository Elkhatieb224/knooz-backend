<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Service;
use App\Support\ServiceChannels;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Catalog services (center / home / emergency) with channel, kind_key, requires.
 * Safe to re-run: upserts by slug.
 */
class ServicesSeeder extends Seeder
{
    /**
     * @return Collection<int, Service>
     */
    public function run(): Collection
    {
        $rows = [
            // —— حجز بالمركز (Figma) ——
            [
                'name_ar' => 'وقود',
                'name_en' => 'Fuel',
                'slug' => 'fuel-center',
                'icon' => 'fuel',
                'description' => 'خدمة تعبئة الوقود',
                'base_price' => 0,
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
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
                'channel' => ServiceChannels::CENTER,
                'kind_key' => 'parts',
                'requires' => [],
                'flow_hint' => 'shop',
                'sort_order' => 90,
            ],
            // —— خدمة عند المنزل ——
            [
                'name_ar' => 'غسيل عند المنزل',
                'name_en' => 'Home Car Wash',
                'slug' => 'car-wash',
                'icon' => 'droplet',
                'description' => 'غسيل السيارات عند موقع العميل',
                'base_price' => 70,
                'channel' => ServiceChannels::HOME,
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
                'channel' => ServiceChannels::HOME,
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
                'channel' => ServiceChannels::HOME,
                'kind_key' => 'tires',
                'requires' => ['location', 'notes'],
                'flow_hint' => 'home_visit',
                'sort_order' => 30,
            ],
            // —— طوارئ الطريق ——
            [
                'name_ar' => 'طوارئ كفرات',
                'name_en' => 'Tire Emergency',
                'slug' => 'tire-emergency',
                'icon' => 'alert',
                'description' => 'خدمة طوارئ إصلاح أو تغيير الكفرات على الطريق',
                'base_price' => 250,
                'channel' => ServiceChannels::EMERGENCY,
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
                'channel' => ServiceChannels::EMERGENCY,
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
                'channel' => ServiceChannels::EMERGENCY,
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
                'channel' => ServiceChannels::EMERGENCY,
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
                'channel' => ServiceChannels::EMERGENCY,
                'kind_key' => 'lockout',
                'requires' => ['location', 'vehicle', 'plate', 'notes'],
                'flow_hint' => 'generic_emergency',
                'sort_order' => 50,
            ],
        ];

        $services = collect();
        foreach ($rows as $row) {
            $service = Service::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    ...$row,
                    'is_active' => true,
                ]
            );
            $services->push($service);
        }

        // Attach only services matching each center's enabled execution channels.
        $centers = Center::query()->get();
        foreach ($centers as $center) {
            $allowed = $center->offeredChannels();
            foreach ($services as $service) {
                $channel = (string) ($service->channel ?: '');
                if ($channel !== '' && ! in_array($channel, $allowed, true)) {
                    continue;
                }
                if ($center->services()->where('services.id', $service->id)->exists()) {
                    continue;
                }
                $center->services()->attach($service->id, [
                    'contract_price' => $service->base_price,
                    'app_profit' => 0,
                    'is_available' => true,
                ]);
            }
        }

        Cache::forget('api:v1:services');
        foreach (array_keys(ServiceChannels::labels()) as $channel) {
            Cache::forget('api:v1:services:'.$channel);
        }

        $this->command?->info('Services seeded: '.$services->count());

        return $services;
    }
}
