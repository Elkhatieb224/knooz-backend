<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates domain seeders. Prefer running a specific class when possible:
 *
 *   php artisan db:seed --class=ServicesSeeder
 *   php artisan db:seed --class=ShopTestProductsSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core catalog & config
            SettingsSeeder::class,
            ServicesSeeder::class,
            ShopProductsSeeder::class,
            ShopTestProductsSeeder::class,
            OffersSeeder::class,
            PackagesSeeder::class,

            // Accounts
            AdminUsersSeeder::class,
            CentersSeeder::class,
            CustomersSeeder::class,
            CompaniesSeeder::class,
            VehiclesSeeder::class,

            // Sample operational data
            BookingsAndOperationsSeeder::class,
            WalletsAndPaymentsSeeder::class,
            InvoicesSeeder::class,
            ComplaintsSeeder::class,
            EmergencyRequestsSeeder::class,
            NotificationsSeeder::class,
        ]);
    }
}
