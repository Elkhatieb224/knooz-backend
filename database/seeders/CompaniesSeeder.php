<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\PaymentMode;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompaniesSeeder extends Seeder
{
    /**
     * @return array{company: Company, owner: CompanyUser, contact: User}
     */
    public function run(): array
    {
        $password = Hash::make('password');

        $company = Company::query()->updateOrCreate(
            ['commercial_register' => 'CR-1234567890'],
            [
                'name' => 'شركة النقل السريع',
                'phone' => '0119988776',
                'email' => 'fleet@fasttransport.test',
                'status' => CompanyStatus::Active,
                'credit_limit' => 20000,
                'usage_cap' => 15000,
                'payment_mode' => PaymentMode::Credit,
                'notes' => 'عميل شركات تجريبي',
            ]
        );

        $owner = CompanyUser::query()->updateOrCreate(
            ['email' => 'company@knooz.test'],
            [
                'company_id' => $company->id,
                'name' => 'مسؤول الشركة',
                'phone' => '0553333333',
                'password' => $password,
                'status' => UserStatus::Active,
                'role' => CompanyUserRole::Owner,
                'theme' => Theme::Light,
            ]
        );

        foreach ([
            [
                'email' => 'manager@knooz.test',
                'name' => 'مدير العمليات',
                'phone' => '0553333335',
                'role' => CompanyUserRole::Manager,
            ],
            [
                'email' => 'accountant@knooz.test',
                'name' => 'محاسب الشركة',
                'phone' => '0553333336',
                'role' => CompanyUserRole::Accountant,
            ],
            [
                'email' => 'viewer@knooz.test',
                'name' => 'موظف مشاهدة',
                'phone' => '0553333337',
                'role' => CompanyUserRole::Viewer,
            ],
        ] as $row) {
            CompanyUser::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'company_id' => $company->id,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'password' => $password,
                    'status' => UserStatus::Active,
                    'role' => $row['role'],
                    'theme' => Theme::Light,
                    'created_by' => $owner->id,
                ]
            );
        }

        $contact = User::query()->updateOrCreate(
            ['email' => 'fleet-contact@knooz.test'],
            [
                'name' => 'جهة اتصال الأسطول',
                'phone' => '0553333334',
                'password' => $password,
                'role' => UserRole::CustomerCompany,
                'status' => UserStatus::Active,
                'company_id' => $company->id,
                'qr_code' => 'QR-COMPANY-CONTACT',
                'nfc_code' => 'NFC-COMPANY-CONTACT',
                'email_verified_at' => now(),
            ]
        );

        if (! $company->users()->where('users.id', $contact->id)->exists()) {
            $company->users()->attach($contact->id, ['role' => 'admin']);
        }

        $this->command?->info('Company seeded: '.$company->name);

        return [
            'company' => $company,
            'owner' => $owner,
            'contact' => $contact,
        ];
    }
}
