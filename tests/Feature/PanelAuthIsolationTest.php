<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CompanyStatus;
use App\Enums\PaymentMode;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAuthIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_access_admin_dashboard(): void
    {
        AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@knooz.test',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $this->post('/admin/login', [
            'login' => 'admin@knooz.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')->assertOk();
    }

    public function test_company_user_cannot_access_admin_panel(): void
    {
        $company = $this->makeCompany('Company A');
        $this->makeCompanyUser($company, 'company@knooz.test');

        $this->post('/company/login', [
            'login' => 'company@knooz.test',
            'password' => 'password',
        ])->assertRedirect(route('company.dashboard'));

        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_center_user_cannot_access_company_panel(): void
    {
        $center = $this->makeCenter('Center A');
        $this->makeCenterUser($center, 'center@knooz.test');

        $this->post('/center/login', [
            'login' => 'center@knooz.test',
            'password' => 'password',
        ])->assertRedirect(route('center.dashboard'));

        $response = $this->get('/company');
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 403,
            'Center user must be blocked from company panel'
        );
    }

    public function test_company_cannot_view_another_company_vehicle(): void
    {
        $companyA = $this->makeCompany('Company A');
        $companyB = $this->makeCompany('Company B', 'CR-B');
        $this->makeCompanyUser($companyA, 'company-a@knooz.test');

        $vehicleB = Vehicle::query()->create([
            'company_id' => $companyB->id,
            'plate_number' => 'B-100',
            'brand' => 'Toyota',
            'model' => 'Camry',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-B-1',
            'nfc_code' => 'NFC-B-1',
        ]);

        $this->post('/company/login', [
            'login' => 'company-a@knooz.test',
            'password' => 'password',
        ])->assertRedirect(route('company.dashboard'));

        $this->get(route('company.vehicles.show', $vehicleB))->assertNotFound();
    }

    public function test_center_cannot_update_other_center_booking(): void
    {
        $centerA = $this->makeCenter('Center A');
        $centerB = $this->makeCenter('Center B');
        $this->makeCenterUser($centerA, 'center-a@knooz.test');

        $customer = User::query()->create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);

        $service = Service::query()->create([
            'name_ar' => 'غسيل',
            'name_en' => 'Wash',
            'slug' => 'wash-test',
            'base_price' => 50,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'user_id' => $customer->id,
            'plate_number' => 'C-100',
            'brand' => 'Honda',
            'model' => 'Civic',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-C-1',
            'nfc_code' => 'NFC-C-1',
        ]);

        $booking = Booking::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $centerB->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'status' => BookingStatus::New,
            'price' => 50,
        ]);

        $this->post('/center/login', [
            'login' => 'center-a@knooz.test',
            'password' => 'password',
        ])->assertRedirect(route('center.dashboard'));

        $this->patch(route('center.bookings.update', $booking), [
            'status' => BookingStatus::Completed->value,
        ])->assertNotFound();
    }

    protected function makeCompany(string $name, string $cr = 'CR-A'): Company
    {
        return Company::query()->create([
            'name' => $name,
            'commercial_register' => $cr,
            'status' => CompanyStatus::Active,
            'payment_mode' => PaymentMode::Credit,
            'credit_limit' => 1000,
            'usage_cap' => 1000,
        ]);
    }

    protected function makeCompanyUser(Company $company, string $email): CompanyUser
    {
        return CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'Company User',
            'email' => $email,
            'password' => 'password',
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }

    protected function makeCenter(string $name): Center
    {
        return Center::query()->create([
            'name' => $name,
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
        ]);
    }

    protected function makeCenterUser(Center $center, string $email): CenterUser
    {
        return CenterUser::query()->create([
            'center_id' => $center->id,
            'name' => 'Center User',
            'email' => $email,
            'password' => 'password',
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
