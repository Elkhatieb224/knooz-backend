<?php

namespace App\Enums;

enum OperationPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Subscription = 'subscription';
    case Credit = 'credit';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوع',
            self::Paid => 'مدفوع',
            self::Subscription => 'ضمن اشتراك',
            self::Credit => 'آجل',
            self::Free => 'مجاني',
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
