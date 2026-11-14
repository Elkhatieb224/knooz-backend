<?php

namespace App\Support;

use App\Enums\CompanyUserRole;
use App\Models\CompanyUser;

/**
 * Role-only permission matrix for company panel users.
 */
final class CompanyPermissions
{
    private const ALL = ['owner', 'manager', 'accountant', 'viewer'];

    /**
     * permission key => role values that hold it.
     *
     * @var array<string, list<string>>
     */
    public const MATRIX = [
        'dashboard.view' => self::ALL,
        'vehicles.view' => self::ALL,
        'operations.view' => self::ALL,
        'invoices.view' => ['owner', 'manager', 'accountant'],
        'subscription.view' => ['owner', 'manager'],
        'wallet.view' => ['owner', 'manager', 'accountant'],
        'reports.view' => ['owner', 'manager', 'accountant'],
        'staff.manage' => ['owner', 'manager'],
        'profile.edit' => self::ALL,
    ];

    public static function resolveRole(CompanyUser|CompanyUserRole|string|null $subject): ?CompanyUserRole
    {
        if ($subject instanceof CompanyUser) {
            $subject = $subject->role;
        }

        if ($subject instanceof CompanyUserRole) {
            return $subject;
        }

        return is_string($subject) ? CompanyUserRole::tryFrom($subject) : null;
    }

    public static function allows(CompanyUser|CompanyUserRole|string|null $subject, string $permission): bool
    {
        $role = self::resolveRole($subject);

        if (! $role) {
            return false;
        }

        return in_array($role->value, self::MATRIX[$permission] ?? [], true);
    }

    /**
     * @return list<string>
     */
    public static function forRole(CompanyUser|CompanyUserRole|string|null $subject): array
    {
        $role = self::resolveRole($subject);

        if (! $role) {
            return [];
        }

        return array_values(array_keys(array_filter(
            self::MATRIX,
            fn (array $roles) => in_array($role->value, $roles, true),
        )));
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::MATRIX);
    }
}
