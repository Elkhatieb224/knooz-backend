<?php

namespace App\Enums;

enum CenterUserRole: string
{
    case Manager = 'manager';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'مدير',
            self::Staff => 'موظف',
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

    public function canViewRevenue(): bool
    {
        return $this === self::Manager;
    }
}
