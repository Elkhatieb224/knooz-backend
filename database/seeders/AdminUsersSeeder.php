<?php

namespace Database\Seeders;

use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    /**
     * @return Collection<string, AdminUser> keyed by role value
     */
    public function run(): Collection
    {
        $password = Hash::make('password');

        $rows = [
            [
                'key' => 'super_admin',
                'name' => 'مدير النظام',
                'email' => 'admin@knooz.test',
                'phone' => '0500000000',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'key' => 'operations',
                'name' => 'مدير التشغيل',
                'email' => 'operations@knooz.test',
                'phone' => '0500000001',
                'role' => UserRole::OperationsManager,
            ],
            [
                'key' => 'accountant',
                'name' => 'المحاسب',
                'email' => 'accountant@knooz.test',
                'phone' => '0500000002',
                'role' => UserRole::Accountant,
            ],
            [
                'key' => 'support',
                'name' => 'فني الدعم',
                'email' => 'support@knooz.test',
                'phone' => '0500000003',
                'role' => UserRole::Support,
            ],
        ];

        $admins = collect();
        foreach ($rows as $row) {
            $admin = AdminUser::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'password' => $password,
                    'role' => $row['role'],
                    'status' => UserStatus::Active,
                    'theme' => Theme::Light,
                ]
            );
            $admins->put($row['key'], $admin);
        }

        $this->command?->info('Admin users: '.$admins->count());

        return $admins;
    }
}
