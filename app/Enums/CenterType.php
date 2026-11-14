<?php

namespace App\Enums;

enum CenterType: string
{
    case Owned = 'owned';
    case Contracted = 'contracted';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'مملوك',
            self::Contracted => 'متعاقد',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
