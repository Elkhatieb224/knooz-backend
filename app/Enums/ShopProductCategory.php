<?php

namespace App\Enums;

enum ShopProductCategory: string
{
    case Tires = 'tires';
    case Oils = 'oils';
    case Batteries = 'batteries';
    case Accessories = 'accessories';
    case Parts = 'parts';

    public function label(): string
    {
        return match ($this) {
            self::Tires => 'الإطارات',
            self::Oils => 'زيوت وفلاتر',
            self::Batteries => 'البطاريات',
            self::Accessories => 'إكسسوارات وزينة',
            self::Parts => 'قطع غيار',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public static function tryLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $case = self::tryFrom($value);

        return $case?->label() ?? $value;
    }
}
