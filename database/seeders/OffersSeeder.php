<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Service;
use Illuminate\Database\Seeder;

class OffersSeeder extends Seeder
{
    public function run(): void
    {
        $wash = Service::query()->where('slug', 'car-wash')->first();
        $oil = Service::query()->where('slug', 'oil-change')->first();

        if ($wash) {
            Offer::query()->updateOrCreate(
                ['title' => 'خصم الغسيل الأسبوعي'],
                [
                    'description' => 'خصم خاص على خدمة الغسيل طوال الأسبوع',
                    'discount_percent' => 20,
                    'service_id' => $wash->id,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addDays(14),
                    'is_active' => true,
                ]
            );
        }

        if ($oil) {
            Offer::query()->updateOrCreate(
                ['title' => 'عرض تغيير الزيت'],
                [
                    'description' => 'خصم على تغيير الزيت لفترة محدودة',
                    'discount_percent' => 15,
                    'service_id' => $oil->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Offers seeded.');
    }
}
