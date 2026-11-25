<?php

namespace Tests\Feature;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Center;
use App\Models\CenterUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCenterCreateWithManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_center_also_creates_manager_account(): void
    {
        $admin = AdminUser::query()->forceCreate([
            'name' => 'Super',
            'email' => 'super-center@knooz.test',
            'phone' => '0501112233',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.centers.store'), [
                'name' => 'مركز الاختبار',
                'type' => CenterType::Contracted->value,
                'status' => CenterStatus::Active->value,
                'city' => 'الرياض',
                'phone' => '0110000000',
                'offers_center_service' => 1,
                'offers_home_service' => 0,
                'offers_emergency_service' => 0,
                'show_revenue' => 1,
                'manager_name' => 'مدير المركز',
                'manager_email' => 'manager.new@center.test',
                'manager_phone' => '0509988776',
                'manager_password' => 'Password123!',
                'manager_password_confirmation' => 'Password123!',
            ])
            ->assertRedirect();

        $center = Center::query()->where('name', 'مركز الاختبار')->first();
        $this->assertNotNull($center);

        $this->assertDatabaseHas('center_users', [
            'email' => 'manager.new@center.test',
            'center_id' => $center->id,
            'role' => CenterUserRole::Manager->value,
            'status' => UserStatus::Active->value,
        ]);

        $manager = CenterUser::query()->where('email', 'manager.new@center.test')->first();
        $this->assertSame($manager->id, $center->manager_center_user_id);
    }

    public function test_create_center_requires_manager_fields(): void
    {
        $admin = AdminUser::query()->forceCreate([
            'name' => 'Super2',
            'email' => 'super-center-2@knooz.test',
            'phone' => '0501112244',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.centers.store'), [
                'name' => 'مركز بدون مسؤول',
                'type' => CenterType::Owned->value,
                'status' => CenterStatus::Active->value,
                'offers_center_service' => 1,
                'offers_home_service' => 0,
                'offers_emergency_service' => 0,
                'show_revenue' => 0,
            ])
            ->assertSessionHasErrors(['manager_name', 'manager_email', 'manager_password']);
    }
}
