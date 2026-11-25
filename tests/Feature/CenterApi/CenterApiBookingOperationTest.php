<?php

namespace Tests\Feature\CenterApi;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\OperationStatus;
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

class CenterApiBookingOperationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_and_reject_booking(): void
    {
        [$token, $center, $customer, $service, $vehicle] = $this->seedContext();

        $booking = Booking::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'status' => BookingStatus::New,
            'price' => 80,
        ]);

        $this->withToken($token)
            ->patchJson('/api/center/v1/bookings/'.$booking->id, [
                'status' => BookingStatus::InProgress->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::InProgress->value);

        $booking2 = Booking::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2),
            'status' => BookingStatus::New,
            'price' => 80,
        ]);

        $this->withToken($token)
            ->patchJson('/api/center/v1/bookings/'.$booking2->id, [
                'status' => BookingStatus::Cancelled->value,
                'notes' => 'المركز ممتلئ',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);
    }

    public function test_lookup_create_and_update_operation(): void
    {
        [$token, $center, $customer, $service, $vehicle] = $this->seedContext();

        $lookup = $this->withToken($token)->postJson('/api/center/v1/operations/lookup', [
            'method' => 'plate',
            'value' => $vehicle->plate_number,
        ])->assertOk();

        $lookup->assertJsonPath('data.vehicle.id', $vehicle->id)
            ->assertJsonPath('data.requires_verification', false);

        $created = $this->withToken($token)->postJson('/api/center/v1/operations', [
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'payment_mode' => 'cash',
            'source' => 'manual',
            'notes' => 'walk-in',
        ])->assertCreated();

        $opId = $created->json('data.0.id');
        $this->assertNotNull($opId);
        $created->assertJsonPath('data.0.status', OperationStatus::InProgress->value)
            ->assertJsonPath('data.0.source', 'manual');

        $this->withToken($token)->patchJson('/api/center/v1/operations/'.$opId, [
            'status' => OperationStatus::Completed->value,
        ])->assertOk()->assertJsonPath('data.status', OperationStatus::Completed->value);
    }

    public function test_qr_and_nfc_lookup(): void
    {
        [$token, , , , $vehicle] = $this->seedContext();

        $this->withToken($token)->postJson('/api/center/v1/operations/lookup', [
            'method' => 'qr',
            'value' => $vehicle->qr_code,
        ])->assertOk()->assertJsonPath('data.vehicle.id', $vehicle->id);

        $this->withToken($token)->postJson('/api/center/v1/operations/lookup', [
            'method' => 'nfc',
            'value' => $vehicle->nfc_code,
        ])->assertOk()->assertJsonPath('data.vehicle.id', $vehicle->id);
    }

    /**
     * @return array{0: string, 1: Center, 2: User, 3: Service, 4: Vehicle}
     */
    protected function seedContext(): array
    {
        $center = Center::query()->create([
            'name' => 'Ops Center',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'show_revenue' => true,
        ]);

        $manager = CenterUser::query()->forceCreate([
            'center_id' => $center->id,
            'name' => 'Manager',
            'email' => 'ops@center.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => CenterUserRole::Manager,
            'theme' => Theme::Light,
        ]);

        $service = Service::query()->create([
            'name_ar' => 'تلميع',
            'name_en' => 'Polish',
            'slug' => 'polish-ops',
            'base_price' => 100,
            'is_active' => true,
        ]);

        $center->services()->attach($service->id, [
            'contract_price' => 90,
            'app_profit' => 10,
            'is_available' => true,
        ]);

        $customer = User::query()->forceCreate([
            'name' => 'Cust',
            'email' => 'cust-ops@test.com',
            'phone' => '0552000002',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);

        $vehicle = Vehicle::query()->create([
            'user_id' => $customer->id,
            'plate_number' => 'OPS-99',
            'brand' => 'Kia',
            'model' => 'K5',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-OPS-99',
            'nfc_code' => 'NFC-OPS-99',
        ]);

        $token = PanelApiToken::issue($manager, 'center')['plain'];

        return [$token, $center, $customer, $service, $vehicle];
    }
}
