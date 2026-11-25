<?php

namespace Database\Seeders;

use App\Enums\PackageType;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Package;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PackagesSeeder extends Seeder
{
    /**
     * @return Collection<string, Package>
     */
    public function run(): Collection
    {
        $wash = Service::query()->where('slug', 'car-wash')->first();
        $oil = Service::query()->where('slug', 'oil-change')->first();

        $individual = Package::query()->updateOrCreate(
            ['name' => 'باقة الأفراد الأساسية'],
            [
                'type' => PackageType::Individual,
                'duration_days' => 30,
                'operations_count' => 8,
                'vehicles_count' => 1,
                'price' => 299,
                'discount_percent' => 10,
                'free_services' => array_filter([$wash?->id]),
                'is_active' => true,
            ]
        );

        $companyPkg = Package::query()->updateOrCreate(
            ['name' => 'باقة الشركات المتقدمة'],
            [
                'type' => PackageType::Company,
                'duration_days' => 90,
                'operations_count' => 100,
                'vehicles_count' => 20,
                'price' => 4999,
                'discount_percent' => 15,
                'free_services' => array_values(array_filter([$wash?->id, $oil?->id])),
                'is_active' => true,
            ]
        );

        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        if ($ahmed) {
            Subscription::query()->updateOrCreate(
                [
                    'user_id' => $ahmed->id,
                    'package_id' => $individual->id,
                ],
                [
                    'starts_at' => now()->subDays(5),
                    'ends_at' => now()->addDays(25),
                    'operations_used' => 1,
                    'status' => SubscriptionStatus::Active,
                ]
            );
        }

        $company = Company::query()->where('commercial_register', 'CR-1234567890')->first();
        if ($company) {
            Subscription::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'package_id' => $companyPkg->id,
                ],
                [
                    'starts_at' => now()->subDays(10),
                    'ends_at' => now()->addDays(80),
                    'operations_used' => 3,
                    'status' => SubscriptionStatus::Active,
                ]
            );
        }

        $packages = collect([
            'individual' => $individual,
            'company' => $companyPkg,
        ]);

        $this->command?->info('Packages: '.$packages->count());

        return $packages;
    }
}
