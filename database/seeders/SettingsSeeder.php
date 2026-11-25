<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_name' => 'كنوز',
            'app_name_en' => 'KNOOZ',
            'support_phone' => '920000000',
            'support_email' => 'support@knooz.test',
            'currency' => 'SAR',
            'default_theme' => 'light',
            'vat_percent' => '15',
            'emergency_radius_km' => '25',
        ];

        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $content = [
            'about' => [
                'title' => 'عن كنوز الطاقة',
                'body' => '<p>كنوز الطاقة تطبيق خدمات السيارات في المملكة. نوفر الوقود والغسيل والصيانة والطوارئ من هاتفك.</p>',
            ],
            'terms' => [
                'title' => 'الشروط والأحكام',
                'body' => '<p>باستخدامك للتطبيق فإنك توافق على شروط الاستخدام وسياسات الخدمة المعتمدة لدى كنوز.</p>',
            ],
            'privacy' => [
                'title' => 'سياسة الخصوصية',
                'body' => '<p>نحترم خصوصيتك ونستخدم بياناتك لتقديم الخدمات وتحسين التجربة وفق الأنظمة المعمول بها.</p>',
            ],
            'faq' => [
                'title' => 'الأسئلة الشائعة',
                'body' => '<p><strong>كيف أحجز خدمة؟</strong><br>من الصفحة الرئيسية اختر الخدمة واتبع الخطوات.</p><p><strong>كيف أشحن المحفظة؟</strong><br>من المحفظة ارفع إيصال التحويل ليتم مراجعته.</p>',
            ],
        ];

        foreach ($content as $page => $payload) {
            Setting::setValue('content.'.$page, array_merge($payload, [
                'updated_at' => now()->toIso8601String(),
            ]));
        }

        $this->command?->info('Settings seeded: '.count($settings).' + content pages');
    }
}
