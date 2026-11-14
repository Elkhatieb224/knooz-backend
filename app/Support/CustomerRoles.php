<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

final class CustomerRoles
{
    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            UserRole::CustomerIndividual->value,
            UserRole::CustomerCompany->value,
        ];
    }

    public static function isCustomer(User|UserRole|string|null $userOrRole): bool
    {
        $role = $userOrRole instanceof User
            ? $userOrRole->role?->value
            : ($userOrRole instanceof UserRole ? $userOrRole->value : $userOrRole);

        return in_array($role, self::values(), true);
    }
}
