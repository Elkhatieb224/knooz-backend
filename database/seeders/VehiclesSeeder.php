<?php

namespace Database\Seeders;

use App\Enums\VehicleStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class VehiclesSeeder extends Seeder
{
    /**
     * @return Collection<string, Vehicle>
     */
    public function run(): Collection
    {
        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        $sara = User::query()->where('email', 'sara@customer.test')->first();
        $company = Company::query()->where('commercial_register', 'CR-1234567890')->first();

        $vehicles = collect();

        if ($ahmed) {
            $vehicles->put('ahmed', Vehicle::query()->updateOrCreate(
                ['plate_number' => 'أ ب ج 1234'],
                [
                    'user_id' => $ahmed->id,
                    'brand' => 'تويوتا',
                    'model' => 'كامري',
                    'color' => 'أبيض',
                    'year' => 2022,
                    'qr_code' => 'QR-VEH-AHMED-1',
                    'nfc_code' => 'NFC-VEH-AHMED-1',
                    'status' => VehicleStatus::Active,
                ]
            ));
        }

        if ($sara) {
            $vehicles->put('sara', Vehicle::query()->updateOrCreate(
                ['plate_number' => 'د هـ و 5678'],
                [
                    'user_id' => $sara->id,
                    'brand' => 'هيونداي',
                    'model' => 'النترا',
                    'color' => 'رمادي',
                    'year' => 2021,
                    'qr_code' => 'QR-VEH-SARA-1',
                    'nfc_code' => 'NFC-VEH-SARA-1',
                    'status' => VehicleStatus::Active,
                ]
            ));
        }

        if ($company) {
            $vehicles->put('company_1', Vehicle::query()->updateOrCreate(
                ['plate_number' => 'ش ر ك 1001'],
                [
                    'company_id' => $company->id,
                    'brand' => 'نيسان',
                    'model' => 'باترول',
                    'color' => 'أسود',
                    'year' => 2023,
                    'qr_code' => 'QR-VEH-CO-1',
                    'nfc_code' => 'NFC-VEH-CO-1',
                    'status' => VehicleStatus::Active,
                ]
            ));

            $vehicles->put('company_2', Vehicle::query()->updateOrCreate(
                ['plate_number' => 'ش ر ك 1002'],
                [
                    'company_id' => $company->id,
                    'brand' => 'فورد',
                    'model' => 'إكسبلورر',
                    'color' => 'أزرق',
                    'year' => 2020,
                    'qr_code' => 'QR-VEH-CO-2',
                    'nfc_code' => 'NFC-VEH-CO-2',
                    'status' => VehicleStatus::Active,
                ]
            ));
        }

        $this->command?->info('Vehicles: '.$vehicles->count());

        return $vehicles;
    }
}
