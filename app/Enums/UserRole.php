<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case OperationsManager = 'operations_manager';
    case Accountant = 'accountant';
    case Support = 'support';
    case CustomerIndividual = 'customer_individual';
    case CustomerCompany = 'customer_company';
    case CenterManager = 'center_manager';
    case BranchEmployee = 'branch_employee';
    case EmergencyEmployee = 'emergency_employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير النظام',
            self::OperationsManager => 'مدير تشغيل',
            self::Accountant => 'محاسب',
            self::Support => 'فني دعم',
            self::CustomerIndividual => 'عميل فردي',
            self::CustomerCompany => 'عميل شركة',
            self::CenterManager => 'مسؤول مركز',
            self::BranchEmployee => 'موظف فرع',
            self::EmergencyEmployee => 'موظف طوارئ',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [
            self::SuperAdmin,
            self::OperationsManager,
            self::Accountant,
            self::Support,
        ], true);
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
