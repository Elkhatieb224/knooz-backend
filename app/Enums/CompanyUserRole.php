<?php

namespace App\Enums;

enum CompanyUserRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'مالك',
            self::Manager => 'مدير',
            self::Accountant => 'محاسب',
            self::Viewer => 'مشاهد',
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

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    /**
     * Permission keys granted to this role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return \App\Support\CompanyPermissions::forRole($this);
    }
}
