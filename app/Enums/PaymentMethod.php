<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Online = 'online';
    case BankTransfer = 'bank_transfer';
    case Wallet = 'wallet';
    case Subscription = 'subscription';
    case Manual = 'manual';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'أونلاين',
            self::BankTransfer => 'تحويل بنكي',
            self::Wallet => 'محفظة',
            self::Subscription => 'اشتراك',
            self::Manual => 'يدوي',
            self::Credit => 'آجل',
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
