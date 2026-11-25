<?php

namespace Tests\Feature\CenterApi;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\OperationStatus;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Operation;
use App\Models\PanelApiToken;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterApiRevenueRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_see_revenue(): void
    {
        // Even when the center flag is on, staff role never sees revenue.
        $center = Center::query()->create([
            'name' => 'Staff Rev Gate',
            'type' => CenterType::Contracted,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);
        $staff = $this->makeUser($center, 'staff@center.test', CenterUserRole::Staff);
        $this->seedCompletedOperation($center, 100);

        $token = PanelApiToken::issue($staff, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.can_view_revenue', false)
            ->assertJsonPath('data.revenue_net', null);

        $this->withToken($token)->getJson('/api/center/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.can_view_revenue', false)
            ->assertJsonPath('data.stats.revenue_today_net', null);
    }

    public function test_contracted_manager_sees_net_revenue_by_default(): void
    {
        $center = Center::query()->create([
            'name' => 'Contracted',
            'type' => CenterType::Contracted,
            'status' => CenterStatus::Active,
            'city' => 'Jeddah',
            'show_revenue' => true,
        ]);

        $manager = $this->makeUser($center, 'contract-mgr@center.test', CenterUserRole::Manager);
        $this->seedCompletedOperation($center, 100, 15);
        $token = PanelApiToken::issue($manager, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.can_view_revenue', true)
            ->assertJsonPath('data.revenue_net', 85);
    }

    public function test_owned_center_hides_revenue_by_default(): void
    {
        $center = $this->makeOwnedCenter();
        $manager = $this->makeUser($center, 'mgr-rev@center.test', CenterUserRole::Manager);
        $this->seedCompletedOperation($center, 100, 15);

        $token = PanelApiToken::issue($manager, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.can_view_revenue', false)
            ->assertJsonPath('data.revenue_net', null);
    }

    public function test_owned_manager_sees_net_when_flag_enabled(): void
    {
        $center = Center::query()->create([
            'name' => 'Owned With Revenue',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);
        $manager = $this->makeUser($center, 'owned-on@center.test', CenterUserRole::Manager);
        $this->seedCompletedOperation($center, 100, 15);
        $token = PanelApiToken::issue($manager, 'center')['plain'];

        $this->withToken($token)->getJson('/api/center/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.can_view_revenue', true)
            ->assertJsonPath('data.revenue_net', 85);
    }

    protected function makeOwnedCenter(): Center
    {
        return Center::query()->create([
            'name' => 'Owned Rev',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => false,
        ]);
    }

    protected function makeUser(Center $center, string $email, CenterUserRole $role): CenterUser
    {
        return CenterUser::query()->forceCreate([
            'center_id' => $center->id,
            'name' => 'User',
            'email' => $email,
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => $role,
            'theme' => Theme::Light,
        ]);
    }

    protected function seedCompletedOperation(Center $center, float $amount, float $appProfit = 10): void
    {
        $service = Service::query()->create([
            'name_ar' => 'خدمة',
            'name_en' => 'Service',
            'slug' => 'svc-'.uniqid(),
            'base_price' => $amount,
            'is_active' => true,
        ]);

        $center->services()->attach($service->id, [
            'contract_price' => $amount,
            'app_profit' => $appProfit,
            'is_available' => true,
        ]);

        $customer = User::query()->forceCreate([
            'name' => 'C',
            'email' => 'c-'.uniqid().'@test.com',
            'phone' => '05'.random_int(10000000, 99999999),
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);

        $vehicle = Vehicle::query()->create([
            'user_id' => $customer->id,
            'plate_number' => 'R-'.random_int(100, 999),
            'brand' => 'Ford',
            'model' => 'Focus',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-'.uniqid(),
            'nfc_code' => 'NFC-'.uniqid(),
        ]);

        Operation::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'status' => OperationStatus::Completed,
            'amount' => $amount,
            'payment_status' => 'paid',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'source' => 'manual',
        ]);
    }
}
