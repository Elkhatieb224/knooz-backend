<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class CustomersSeeder extends Seeder
{
    /**
     * @return Collection<string, User>
     */
    public function run(): Collection
    {
        $password = Hash::make('password');

        $rows = [
            'ahmed' => [
                'name' => 'أحمد العميل',
                'email' => 'ahmed@customer.test',
                'phone' => '0551111111',
                'qr_code' => 'QR-USER-AHMED',
                'nfc_code' => 'NFC-USER-AHMED',
            ],
            'sara' => [
                'name' => 'سارة العميل',
                'email' => 'sara@customer.test',
                'phone' => '0552222222',
                'qr_code' => 'QR-USER-SARA',
                'nfc_code' => 'NFC-USER-SARA',
            ],
        ];

        $customers = collect();
        foreach ($rows as $key => $row) {
            $customers->put(
                $key,
                User::query()->updateOrCreate(
                    ['email' => $row['email']],
                    [
                        ...$row,
                        'password' => $password,
                        'role' => UserRole::CustomerIndividual,
                        'status' => UserStatus::Active,
                        'email_verified_at' => now(),
                    ]
                )
            );
        }

        $this->command?->info('Customers: '.$customers->count());

        return $customers;
    }
}
