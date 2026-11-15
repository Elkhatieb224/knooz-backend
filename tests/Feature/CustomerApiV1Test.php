<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Service;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_login(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'أحمد',
            'email' => 'ahmed@app.test',
            'phone' => '0559998877',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city' => 'الرياض',
        ]);

        $register->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'wallet_balance']]]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login' => '0559998877',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonPath('success', true);
    }

    public function test_admin_cannot_login_to_customer_api(): void
    {
        AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@knooz.test',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        // Admin lives in admin_users, not users — ensure a staff user on users table is blocked
        User::query()->create([
            'name' => 'Ops',
            'email' => 'ops@staff.test',
            'phone' => '0550000111',
            'password' => 'password',
            'role' => UserRole::OperationsManager,
            'status' => UserStatus::Active,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'ops@staff.test',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_vehicle_crud_is_scoped_to_owner(): void
    {
        $owner = $this->makeCustomer('owner@test.com', '0551111001');
        $other = $this->makeCustomer('other@test.com', '0551111002');

        $ownerToken = $owner->createToken('mobile')->plainTextToken;

        $create = $this->withToken($ownerToken)->postJson('/api/v1/vehicles', [
            'plate_number' => 'ABC-1234',
            'brand' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
        ])->assertCreated();

        $vehicleId = $create->json('data.id');

        $otherToken = $other->createToken('mobile')->plainTextToken;
        $this->withToken($otherToken)->getJson('/api/v1/vehicles/'.$vehicleId)->assertNotFound();
    }

    public function test_booking_create_cancel_and_idor(): void
    {
        $user = $this->makeCustomer('booker@test.com', '0552222001');
        $other = $this->makeCustomer('intruder@test.com', '0552222002');
        $center = $this->makeCenter();
        $service = $this->makeService();
        $vehicle = Vehicle::query()->create([
            'user_id' => $user->id,
            'plate_number' => 'B-99',
            'brand' => 'Honda',
            'model' => 'Civic',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-B99',
            'nfc_code' => 'NFC-B99',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;
        $created = $this->withToken($token)->postJson('/api/v1/bookings', [
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'notes' => 'غسيل',
        ])->assertCreated();

        $bookingId = $created->json('data.id');

        $otherToken = $other->createToken('mobile')->plainTextToken;
        $this->withToken($otherToken)->getJson('/api/v1/bookings/'.$bookingId)->assertNotFound();

        $this->withToken($token)->postJson('/api/v1/bookings/'.$bookingId.'/cancel')->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);
    }

    public function test_wallet_recharge_and_home_and_shop(): void
    {
        $user = $this->makeCustomer('wallet@test.com', '0553333001');
        Wallet::query()->create(['user_id' => $user->id, 'balance' => 50]);
        ShopProduct::query()->create([
            'name_ar' => 'زيت موبيل',
            'slug' => 'mobil-oil-1',
            'category' => 'oils',
            'brand' => 'Mobil',
            'price' => 120,
            'stock' => 10,
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.wallet_balance', 50);
        $this->withToken($token)->getJson('/api/v1/shop/products?category=oils')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->postJson('/api/v1/wallet/recharge', [
            'amount' => 100,
            'method' => 'bank_transfer',
            'reference' => 'REF-1',
        ])->assertCreated();
    }

    public function test_emergency_request_create(): void
    {
        $user = $this->makeCustomer('em@test.com', '0554444001');
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/emergency', [
            'kind' => 'tire',
            'lat' => 24.7,
            'lng' => 46.6,
            'address' => 'الرياض',
            'payload' => ['service' => 'patch'],
        ])->assertCreated()->assertJsonPath('success', true);
    }

    public function test_sanctum_middleware_rejects_missing_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_otp_stub_accepts_any_code_and_resets_password(): void
    {
        $this->makeCustomer('otp@test.com', '0555555001');

        $this->postJson('/api/v1/auth/forgot-password', [
            'login' => '0555555001',
        ])->assertOk()->assertJsonPath('data.sent', true);

        $verify = $this->postJson('/api/v1/auth/verify-otp', [
            'login' => '0555555001',
            'otp' => '000000',
        ])->assertOk();

        $resetToken = $verify->json('data.reset_token');
        $this->assertNotEmpty($resetToken);

        $this->postJson('/api/v1/auth/reset-password', [
            'login' => '0555555001',
            'reset_token' => $resetToken,
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'login' => '555555001',
            'password' => 'newpass123',
        ])->assertOk()->assertJsonPath('success', true);
    }

    protected function makeCustomer(string $email, string $phone): User
    {
        return User::query()->create([
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

    protected function makeCenter(): Center
    {
        return Center::query()->create([
            'name' => 'Center Test',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Active,
            'city' => 'Riyadh',
            'lat' => 24.7,
            'lng' => 46.6,
        ]);
    }

    protected function makeService(): Service
    {
        return Service::query()->create([
            'name_ar' => 'غسيل',
            'name_en' => 'Wash',
            'slug' => 'wash-'.uniqid(),
            'base_price' => 50,
            'is_active' => true,
        ]);
    }
}
