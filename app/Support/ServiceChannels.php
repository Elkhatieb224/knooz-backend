<?php

namespace App\Support;

final class ServiceChannels
{
    public const CENTER = 'center';
    public const HOME = 'home';
    public const EMERGENCY = 'emergency';
    public const SHOP = 'shop';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::CENTER => 'مركز خدمة',
            self::HOME => 'عند المنزل',
            self::EMERGENCY => 'طوارئ الطريق',
            self::SHOP => 'متجر',
        ];
    }

    /**
     * Requirement keys a service may declare.
     *
     * @return array<string, string>
     */
    public static function requirementLabels(): array
    {
        return [
            'location' => 'موقع التنفيذ (إلزامي للطوارئ)',
            'destination' => 'وجهة النقل',
            'vehicle' => 'اختيار مركبة',
            'plate' => 'رقم اللوحة',
            'notes' => 'ملاحظات',
            'photos' => 'صور',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function flowHints(): array
    {
        return [
            'branch' => 'حجز مركز',
            'home_visit' => 'خدمة منزلية',
            'tire_repair' => 'بنشر',
            'fuel' => 'وقود',
            'tow' => 'سطحة',
            'generic_emergency' => 'طوارئ عام',
            'shop' => 'متجر',
        ];
    }
}
