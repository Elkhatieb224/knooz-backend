<?php

namespace Database\Seeders;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class CentersSeeder extends Seeder
{
    /**
     * @return array{centers: Collection<string, Center>, employees: Collection<string, User>}
     */
    public function run(): array
    {
        $password = Hash::make('password');
        $services = Service::query()->get();

        $ownedCenter = Center::query()->updateOrCreate(
            ['email' => 'riyadh@knooz.test'],
            [
                'name' => 'مركز كنوز الرياض',
                'type' => CenterType::Owned,
                'phone' => '0112345678',
                'address' => 'طريق الملك فهد',
                'city' => 'الرياض',
                'lat' => 24.7136000,
                'lng' => 46.6753000,
                'status' => CenterStatus::Active,
                'show_revenue' => false,
                'offers_center_service' => true,
                'offers_home_service' => true,
                'offers_emergency_service' => true,
            ]
        );

        $centerUser = CenterUser::query()->updateOrCreate(
            ['email' => 'center@knooz.test'],
            [
                'center_id' => $ownedCenter->id,
                'name' => 'مسؤول مركز الرياض',
                'phone' => '0500000010',
                'password' => $password,
                'status' => UserStatus::Active,
                'theme' => Theme::Light,
            ]
        );
        $ownedCenter->update(['manager_center_user_id' => $centerUser->id]);

        $contractedCenter = Center::query()->updateOrCreate(
            ['email' => 'jeddah@partner.test'],
            [
                'name' => 'مركز النخبة جدة',
                'type' => CenterType::Contracted,
                'phone' => '0123456789',
                'address' => 'حي الروضة',
                'city' => 'جدة',
                'lat' => 21.5433000,
                'lng' => 39.1728000,
                'status' => CenterStatus::Active,
                'show_revenue' => true,
                // متعاقد: حجز مركز + منزل فقط — بدون طوارئ طريق
                'offers_center_service' => true,
                'offers_home_service' => true,
                'offers_emergency_service' => false,
            ]
        );

        $centers = collect([
            'owned' => $ownedCenter,
            'contracted' => $contractedCenter,
        ]);

        foreach ($centers as $center) {
            $isContracted = $center->type === CenterType::Contracted;
            $allowed = $center->offeredChannels();
            foreach ($services as $service) {
                $channel = (string) ($service->channel ?: '');
                if ($channel !== '' && ! in_array($channel, $allowed, true)) {
                    continue;
                }
                if ($center->services()->where('services.id', $service->id)->exists()) {
                    continue;
                }
                $center->services()->attach($service->id, [
                    'contract_price' => $isContracted ? $service->base_price * 0.8 : $service->base_price,
                    'app_profit' => $isContracted ? $service->base_price * 0.2 : 0,
                    'is_available' => true,
                ]);
            }
        }

        $branchEmployee = User::query()->updateOrCreate(
            ['email' => 'employee@knooz.test'],
            [
                'name' => 'موظف الفرع',
                'phone' => '0500000011',
                'password' => $password,
                'role' => UserRole::BranchEmployee,
                'status' => UserStatus::Active,
                'center_id' => $ownedCenter->id,
                'email_verified_at' => now(),
            ]
        );

        $emergencyEmployee = User::query()->updateOrCreate(
            ['email' => 'emergency@knooz.test'],
            [
                'name' => 'موظف الطوارئ',
                'phone' => '0500000012',
                'password' => $password,
                'role' => UserRole::EmergencyEmployee,
                'status' => UserStatus::Active,
                'center_id' => $ownedCenter->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Centers: '.$centers->count());

        return [
            'centers' => $centers,
            'center_user' => $centerUser,
            'employees' => collect([
                'branch' => $branchEmployee,
                'emergency' => $emergencyEmployee,
            ]),
        ];
    }
}
