<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class Roles
{
    /**
     * @return list<string>
     */
    public static function adminRoles(): array
    {
        return [
            UserRole::SuperAdmin->value,
            UserRole::OperationsManager->value,
            UserRole::Accountant->value,
            UserRole::Support->value,
        ];
    }

    public static function isAdmin(string|UserRole|null $role): bool
    {
        if ($role === null) {
            return false;
        }

        $value = $role instanceof UserRole ? $role->value : $role;

        return in_array($value, self::adminRoles(), true);
    }

    public static function isCompany(string|UserRole|null $role): bool
    {
        if ($role === null) {
            return false;
        }

        $value = $role instanceof UserRole ? $role->value : $role;

        return $value === UserRole::CustomerCompany->value;
    }

    public static function isCenter(string|UserRole|null $role): bool
    {
        if ($role === null) {
            return false;
        }

        $value = $role instanceof UserRole ? $role->value : $role;

        return $value === UserRole::CenterManager->value;
    }

    public static function isPanelUser(string|UserRole|null $role): bool
    {
        return self::isAdmin($role) || self::isCompany($role) || self::isCenter($role);
    }

    public static function homeRouteFor(User $user): string
    {
        if (self::isAdmin($user->role)) {
            return 'admin.dashboard';
        }

        if (self::isCompany($user->role)) {
            return 'company.dashboard';
        }

        if (self::isCenter($user->role)) {
            return 'center.dashboard';
        }

        return 'login';
    }

    /**
     * @return array<string, string>
     */
    public static function adminLabels(): array
    {
        return collect(self::adminRoles())
            ->mapWithKeys(fn (string $role) => [$role => UserRole::from($role)->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function allLabels(): array
    {
        return UserRole::labels();
    }
}
