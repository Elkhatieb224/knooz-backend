<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Credit = 'credit';
    case Subscription = 'subscription';
    case Prepaid = 'prepaid';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'دفع آجل',
            self::Subscription => 'اشتراك',
            self::Prepaid => 'مسبق الدفع',
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
