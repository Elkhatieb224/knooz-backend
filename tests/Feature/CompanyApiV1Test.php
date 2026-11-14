<?php

namespace Tests\Feature;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\OperationStatus;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\Center;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Operation;
use App\Models\PanelApiToken;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds(): void
    {
        $company = $this->makeCompany('A');
        $this->makeCompanyUser($company, 'company@knooz.test');

        $this->postJson('/api/company/v1/auth/login', [
            'login' => 'company@knooz.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'company@knooz.test')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'company' => ['id', 'name']]]]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        $company = $this->makeCompany('A');
        $this->makeCompanyUser($company, 'company@knooz.test');

        $this->postJson('/api/company/v1/auth/login', [
            'login' => 'company@knooz.test',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_inactive_company_cannot_login(): void
    {
        foreach ([CompanyStatus::Inactive, CompanyStatus::Suspended] as $i => $status) {
            $company = $this->makeCompany('Inactive '.$i, $status);
            $this->makeCompanyUser($company, "inactive{$i}@knooz.test");

            $this->postJson('/api/company/v1/auth/login', [
                'login' => "inactive{$i}@knooz.test",
                'password' => 'password',
            ])->assertStatus(422);
        }
    }

    public function test_inactive_user_cannot_login(): void
    {
        $company = $this->makeCompany('A');
        $this->makeCompanyUser($company, 'off@knooz.test', UserStatus::Inactive);

        $this->postJson('/api/company/v1/auth/login', [
            'login' => 'off@knooz.test',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_existing_token_blocked_when_company_becomes_inactive(): void
    {
        $company = $this->makeCompany('A');
        $user = $this->makeCompanyUser($company, 'a@knooz.test');
        $token = PanelApiToken::issue($user, 'company', 'company-app')['plain'];

        $company->update(['status' => CompanyStatus::Suspended]);

        $this->withToken($token)->getJson('/api/company/v1/auth/me')->assertStatus(403);
    }

    public function test_protected_routes_require_auth(): void
    {
        $this->getJson('/api/company/v1/auth/me')->assertStatus(401);
        $this->getJson('/api/company/v1/dashboard')->assertStatus(401);
        $this->getJson('/api/company/v1/vehicles')->assertStatus(401);
    }

    public function test_center_token_is_rejected(): void
    {
        $center = Center::query()->create([
            'name' => 'C',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);
        $centerUser = \App\Models\CenterUser::query()->create([
            'center_id' => $center->id,
            'name' => 'CU',
            'email' => 'cu@center.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
        $token = PanelApiToken::issue($centerUser, 'center')['plain'];

        $this->withToken($token)->getJson('/api/company/v1/vehicles')->assertStatus(403);
    }

    public function test_me_dashboard_vehicles_logout(): void
    {
        $company = $this->makeCompany('A');
        $user = $this->makeCompanyUser($company, 'a@knooz.test');
        Wallet::query()->create(['company_id' => $company->id, 'balance' => 250]);
        $owner = $this->makeCustomer('owner-a@test.com', '0551000011');
        $vehicle = $this->makeVehicle($company, $owner, 'AAA-111');
        $this->makeOperation($vehicle, $owner, 100);

        $token = PanelApiToken::issue($user, 'company', 'company-app')['plain'];

        $this->withToken($token)->getJson('/api/company/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'a@knooz.test');

        $this->withToken($token)->getJson('/api/company/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.vehicles', 1)
            ->assertJsonPath('data.stats.operations', 1)
            ->assertJsonPath('data.company.wallet_balance', 250)
            ->assertJsonCount(1, 'data.recent_operations');

        $this->withToken($token)->getJson('/api/company/v1/vehicles?q=AAA')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.plate_number', 'AAA-111');

        $this->withToken($token)->getJson('/api/company/v1/vehicles/'.$vehicle->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.operations');

        $this->withToken($token)->getJson('/api/company/v1/operations?status=pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)->getJson('/api/company/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 250);

        $this->withToken($token)->getJson('/api/company/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.vehicles_count', 1)
            ->assertJsonPath('data.operations_by_status.pending', 1)
            ->assertJsonPath('data.total_spend', 100);

        $this->withToken($token)->getJson('/api/company/v1/invoices')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/subscriptions')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/notifications')->assertOk();

        $this->withToken($token)->postJson('/api/company/v1/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/auth/me')->assertStatus(401);
    }

    public function test_update_profile(): void
    {
        $company = $this->makeCompany('A');
        $user = $this->makeCompanyUser($company, 'a@knooz.test');
        $token = PanelApiToken::issue($user, 'company', 'company-app')['plain'];

        $this->withToken($token)->putJson('/api/company/v1/auth/profile', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_forgot_password_responds_ok(): void
    {
        $company = $this->makeCompany('A');
        $this->makeCompanyUser($company, 'a@knooz.test');

        $this->postJson('/api/company/v1/auth/forgot-password', ['email' => 'a@knooz.test'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_company_cannot_see_other_company_data(): void
    {
        $companyA = $this->makeCompany('A');
        $companyB = $this->makeCompany('B');
        $userA = $this->makeCompanyUser($companyA, 'a@knooz.test');
        $this->makeCompanyUser($companyB, 'b@knooz.test');
        $token = PanelApiToken::issue($userA, 'company', 'company-app')['plain'];

        $ownerA = $this->makeCustomer('owner-a@test.com', '0551000011');
        $ownerB = $this->makeCustomer('owner-b@test.com', '0551000012');
        $vehicleA = $this->makeVehicle($companyA, $ownerA, 'AAA-111');
        $vehicleB = $this->makeVehicle($companyB, $ownerB, 'BBB-222');
        $this->makeOperation($vehicleB, $ownerB, 300);

        $this->withToken($token)->getJson('/api/company/v1/vehicles/'.$vehicleB->id)
            ->assertNotFound();

        $this->withToken($token)->getJson('/api/company/v1/vehicles/'.$vehicleA->id)
            ->assertOk();

        $this->withToken($token)->getJson('/api/company/v1/vehicles')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissing(['plate_number' => 'BBB-222']);

        $this->withToken($token)->getJson('/api/company/v1/operations')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->withToken($token)->getJson('/api/company/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.total_spend', 0);
    }

    protected function makeCompany(string $name, CompanyStatus $status = CompanyStatus::Active): Company
    {
        return Company::query()->create([
            'name' => 'Company '.$name,
            'status' => $status,
        ]);
    }

    protected function makeCompanyUser(
        Company $company,
        string $email,
        UserStatus $status = UserStatus::Active,
        CompanyUserRole $role = CompanyUserRole::Owner,
    ): CompanyUser {
        return CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'Company User',
            'email' => $email,
            'password' => 'password',
            'status' => $status,
            'role' => $role,
            'theme' => Theme::Light,
        ]);
    }

    protected function makeCustomer(string $email, string $phone): User
    {
        return User::query()->create([
            'name' => 'Owner '.$email,
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);
    }

    protected function makeVehicle(Company $company, User $owner, string $plate): Vehicle
    {
        return Vehicle::query()->create([
            'user_id' => $owner->id,
            'company_id' => $company->id,
            'plate_number' => $plate,
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-'.$plate,
            'nfc_code' => 'NFC-'.$plate,
        ]);
    }

    protected function makeOperation(Vehicle $vehicle, User $owner, float $amount): Operation
    {
        $center = Center::query()->firstOrCreate(['name' => 'Test Center'], [
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);
        $service = Service::query()->firstOrCreate(['slug' => 'wash-co'], [
            'name_ar' => 'غسيل',
            'name_en' => 'Wash',
            'base_price' => 50,
            'is_active' => true,
        ]);

        return Operation::query()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'status' => OperationStatus::Pending,
            'amount' => $amount,
        ]);
    }
}
