<?php

namespace Tests\Feature;

use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelBasicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/users')->assertRedirect(route('admin.login'));
        $this->get('/admin/roles')->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_open_dashboard_and_create_admin(): void
    {
        $admin = $this->makeAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.roles.create'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.store'), [
                'name' => 'مشرف جديد',
                'email' => 'new-admin@knooz.test',
                'phone' => '0500999888',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => UserRole::Support->value,
                'status' => UserStatus::Active->value,
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('admin_users', [
            'email' => 'new-admin@knooz.test',
            'role' => UserRole::Support->value,
        ]);
    }

    public function test_support_cannot_manage_roles(): void
    {
        $support = $this->makeAdmin(UserRole::Support);

        $this->actingAs($support, 'admin')
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_package_can_be_updated_from_settings(): void
    {
        $admin = $this->makeAdmin(UserRole::SuperAdmin);
        $package = Package::query()->create([
            'name' => 'باقة اختبار',
            'type' => 'individual',
            'duration_days' => 30,
            'operations_count' => 5,
            'vehicles_count' => 1,
            'price' => 100,
            'discount_percent' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.packages.update', $package), [
                'name' => 'باقة محدثة',
                'type' => 'individual',
                'duration_days' => 45,
                'operations_count' => 8,
                'vehicles_count' => 2,
                'price' => 150,
                'discount_percent' => 5,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'باقة محدثة',
            'duration_days' => 45,
        ]);
    }

    public function test_admin_login_route_is_throttled(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.login.store');
        $this->assertNotNull($route);
        $this->assertTrue(collect($route->gatherMiddleware())->contains(
            fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'throttle:')
        ));
    }

    private function makeAdmin(UserRole $role): AdminUser
    {
        return AdminUser::query()->forceCreate([
            'name' => 'Admin '.$role->value,
            'email' => $role->value.'@knooz.test',
            'phone' => '050'.random_int(1000000, 9999999),
            'password' => 'password',
            'role' => $role,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
