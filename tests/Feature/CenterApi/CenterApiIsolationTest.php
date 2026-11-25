<?php

namespace Tests\Feature\CenterApi;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\PanelApiToken;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterApiIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_center_cannot_view_other_center_booking(): void
    {
        $centerA = $this->makeCenter('A');
        $centerB = $this->makeCenter('B');
        $userA = $this->makeCenterUser($centerA, 'a@center.test');
        $tokenA = PanelApiToken::issue($userA, 'center')['plain'];

        $customer = $this->makeCustomer();
        $service = $this->makeService();
        $vehicle = $this->makeVehicle($customer);

        $bookingB = Booking::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $centerB->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'status' => BookingStatus::New,
            'price' => 50,
        ]);

        $this->withToken($tokenA)
            ->getJson('/api/center/v1/bookings/'.$bookingB->id)
            ->assertNotFound();

        $this->withToken($tokenA)
            ->patchJson('/api/center/v1/bookings/'.$bookingB->id, [
                'status' => BookingStatus::InProgress->value,
            ])
            ->assertNotFound();
    }

    protected function makeCenter(string $name): Center
    {
        return Center::query()->create([
            'name' => $name,
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);
    }

    protected function makeCenterUser(Center $center, string $email): CenterUser
    {
        return CenterUser::query()->forceCreate([
            'center_id' => $center->id,
            'name' => 'CU',
            'email' => $email,
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => CenterUserRole::Manager,
            'theme' => Theme::Light,
        ]);
    }

    protected function makeCustomer(): User
    {
        return User::query()->forceCreate([
            'name' => 'Customer',
            'email' => 'cust-iso@test.com',
            'phone' => '0551000001',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);
    }

    protected function makeService(): Service
    {
        return Service::query()->create([
            'name_ar' => 'غسيل',
            'name_en' => 'Wash',
            'slug' => 'wash-iso',
            'base_price' => 50,
            'is_active' => true,
        ]);
    }

    protected function makeVehicle(User $user): Vehicle
    {
        return Vehicle::query()->create([
            'user_id' => $user->id,
            'plate_number' => 'ISO-1',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-ISO-1',
            'nfc_code' => 'NFC-ISO-1',
        ]);
    }
}
