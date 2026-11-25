<?php

namespace Tests\Feature;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\ServiceChannels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterServiceChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_centers_by_channel_flag(): void
    {
        $centerOnly = $this->makeCenter([
            'name' => 'Center Only',
            'offers_center_service' => true,
            'offers_home_service' => false,
            'offers_emergency_service' => false,
        ]);
        $homeOnly = $this->makeCenter([
            'name' => 'Home Only',
            'lat' => 24.8,
            'lng' => 46.7,
            'offers_center_service' => false,
            'offers_home_service' => true,
            'offers_emergency_service' => false,
        ]);
        $emergencyOnly = $this->makeCenter([
            'name' => 'Emergency Only',
            'lat' => 24.9,
            'lng' => 46.8,
            'offers_center_service' => false,
            'offers_home_service' => false,
            'offers_emergency_service' => true,
        ]);

        $user = $this->makeCustomer('channels@test.com', '0559000001');
        $token = $user->createToken('mobile')->plainTextToken;

        $centerIds = collect($this->withToken($token)->getJson('/api/v1/centers?channel=center')
            ->assertOk()
            ->json('data'))->pluck('id')->all();
        $this->assertContains($centerOnly->id, $centerIds);
        $this->assertNotContains($homeOnly->id, $centerIds);
        $this->assertNotContains($emergencyOnly->id, $centerIds);

        $homeIds = collect($this->withToken($token)->getJson('/api/v1/centers?channel=home')
            ->assertOk()
            ->json('data'))->pluck('id')->all();
        $this->assertContains($homeOnly->id, $homeIds);
        $this->assertNotContains($centerOnly->id, $homeIds);

        $emergencyIds = collect($this->withToken($token)->getJson('/api/v1/centers?channel=emergency')
            ->assertOk()
            ->json('data'))->pluck('id')->all();
        $this->assertContains($emergencyOnly->id, $emergencyIds);
        $this->assertNotContains($centerOnly->id, $emergencyIds);
    }

    public function test_booking_rejected_when_center_does_not_offer_channel(): void
    {
        $center = $this->makeCenter([
            'offers_center_service' => false,
            'offers_home_service' => true,
            'offers_emergency_service' => false,
        ]);
        $service = $this->makeService(['channel' => ServiceChannels::CENTER]);
        $center->services()->attach($service->id, [
            'contract_price' => 50,
            'app_profit' => 0,
            'is_available' => true,
        ]);

        $user = $this->makeCustomer('book-channel@test.com', '0559000002');
        $vehicle = Vehicle::query()->create([
            'user_id' => $user->id,
            'plate_number' => 'CH-1',
            'brand' => 'Toyota',
            'model' => 'Camry',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-CH1',
            'nfc_code' => 'NFC-CH1',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;
        $this->withToken($token)->postJson('/api/v1/bookings', [
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertStatus(422);
    }

    public function test_emergency_assigns_only_centers_offering_emergency(): void
    {
        $wrong = $this->makeCenter([
            'name' => 'No Emergency',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'offers_center_service' => true,
            'offers_home_service' => true,
            'offers_emergency_service' => false,
        ]);
        $right = $this->makeCenter([
            'name' => 'Emergency Hub',
            'lat' => 24.72,
            'lng' => 46.68,
            'offers_center_service' => false,
            'offers_home_service' => false,
            'offers_emergency_service' => true,
        ]);
        $centerUser = CenterUser::query()->create([
            'center_id' => $right->id,
            'name' => 'Emergency Manager',
            'email' => 'em-mgr@test.com',
            'phone' => '0509000099',
            'password' => 'password',
            'status' => UserStatus::Active,
        ]);

        $user = $this->makeCustomer('em-route@test.com', '0559000003');
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/emergency', [
            'kind' => 'tire',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'address' => 'الرياض',
        ])->assertCreated();

        $this->assertSame($right->id, $response->json('data.center_id'));
        $this->assertNotSame($wrong->id, $response->json('data.center_id'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $centerUser->getMorphClass(),
            'notifiable_id' => $centerUser->id,
        ]);
    }

    public function test_home_visit_assigns_only_home_capable_center(): void
    {
        $wrong = $this->makeCenter([
            'name' => 'Branch Only',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'offers_center_service' => true,
            'offers_home_service' => false,
            'offers_emergency_service' => false,
        ]);
        $right = $this->makeCenter([
            'name' => 'Mobile Team',
            'lat' => 24.714,
            'lng' => 46.676,
            'offers_center_service' => false,
            'offers_home_service' => true,
            'offers_emergency_service' => false,
        ]);

        $user = $this->makeCustomer('home-route@test.com', '0559000004');
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/home-visits', [
            'kind' => 'wash',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'address' => 'حي النرجس',
        ])->assertCreated();

        $this->assertSame($right->id, $response->json('data.center_id'));
        $this->assertNotSame($wrong->id, $response->json('data.center_id'));
    }

    protected function makeCustomer(string $email, string $phone): User
    {
        return User::query()->forceCreate([
            'name' => 'Customer',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
            'qr_code' => 'USR-'.uniqid(),
            'nfc_code' => 'NFC-'.uniqid(),
            'loyalty_points' => 0,
        ]);
    }

    protected function makeCenter(array $overrides = []): Center
    {
        return Center::query()->create(array_merge([
            'name' => 'Center',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'lat' => 24.7,
            'lng' => 46.6,
            'offers_center_service' => true,
            'offers_home_service' => true,
            'offers_emergency_service' => true,
        ], $overrides));
    }

    protected function makeService(array $overrides = []): Service
    {
        return Service::query()->create(array_merge([
            'name_ar' => 'خدمة',
            'name_en' => 'Service',
            'slug' => 'svc-'.uniqid(),
            'base_price' => 50,
            'channel' => ServiceChannels::CENTER,
            'is_active' => true,
        ], $overrides));
    }
}
