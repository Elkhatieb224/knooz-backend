<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\AdminUser;
use App\Models\User;

class Permissions
{
    /**
     * Permission => roles that have full access.
     *
     * @var array<string, list<string>>
     */
    public const MATRIX = [
        'dashboard.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
        'users.manage' => ['super_admin', 'operations_manager'],
        'users.activate' => ['super_admin', 'operations_manager', 'support'],
        'users.view' => ['super_admin', 'operations_manager', 'support'],
        'companies.manage' => ['super_admin', 'operations_manager'],
        'companies.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
        'companies.invoices' => ['super_admin', 'operations_manager', 'accountant'],
        'centers.manage' => ['super_admin', 'operations_manager'],
        'centers.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
        'bookings.manage' => ['super_admin', 'operations_manager', 'support'],
        'bookings.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
        'payments.manage' => ['super_admin', 'accountant'],
        'payments.view' => ['super_admin', 'accountant'],
        'reports.financial' => ['super_admin', 'accountant'],
        'reports.operations' => ['super_admin', 'operations_manager'],
        'offers.manage' => ['super_admin', 'operations_manager'],
        'shop.manage' => ['super_admin', 'operations_manager'],
        'settings.manage' => ['super_admin'],
        'roles.manage' => ['super_admin'],
        'complaints.manage' => ['super_admin', 'operations_manager', 'support'],
        'vehicles.manage' => ['super_admin', 'operations_manager'],
        'vehicles.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
        'notifications.view' => ['super_admin', 'operations_manager', 'accountant', 'support'],
    ];

    /**
     * Static matrix for roles UI (labels).
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'dashboard.view' => 'عرض لوحة الرئيسية',
            'users.manage' => 'إدارة المستخدمين',
            'users.activate' => 'تفعيل/إيقاف المستخدمين',
            'users.view' => 'عرض المستخدمين',
            'companies.manage' => 'إدارة الشركات',
            'companies.view' => 'عرض الشركات',
            'companies.invoices' => 'فواتير الشركات',
            'centers.manage' => 'إدارة المراكز',
            'centers.view' => 'عرض المراكز',
            'bookings.manage' => 'إدارة الطلبات والحجوزات',
            'bookings.view' => 'عرض الطلبات',
            'payments.manage' => 'إدارة المدفوعات والمحافظ',
            'payments.view' => 'عرض المدفوعات',
            'reports.financial' => 'التقارير المالية',
            'reports.operations' => 'تقارير التشغيل',
            'offers.manage' => 'إدارة العروض',
            'shop.manage' => 'إدارة منتجات المتجر',
            'settings.manage' => 'الإعدادات',
            'roles.manage' => 'الصلاحيات والأدوار',
            'complaints.manage' => 'إدارة الشكاوى',
            'vehicles.manage' => 'إدارة المركبات',
            'vehicles.view' => 'عرض المركبات',
            'notifications.view' => 'الإشعارات',
        ];
    }

    public static function allows(AdminUser|User|UserRole|string|null $userOrRole, string $permission): bool
    {
        $roles = self::MATRIX[$permission] ?? [];

        if ($userOrRole instanceof AdminUser || $userOrRole instanceof User) {
            $role = $userOrRole->role?->value;
        } elseif ($userOrRole instanceof UserRole) {
            $role = $userOrRole->value;
        } else {
            $role = $userOrRole;
        }

        if ($role === null) {
            return false;
        }

        return in_array($role, $roles, true);
    }
}
