<?php

namespace Tests\Feature;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTenantStatusGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_company_cannot_login_to_web_panel(): void
    {
        $company = Company::query()->create([
            'name' => 'Suspended Co',
            'status' => CompanyStatus::Suspended,
        ]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'Owner',
            'email' => 'susp@co.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => CompanyUserRole::Owner,
            'theme' => Theme::Light,
        ]);

        $this->post(route('company.login.store'), [
            'login' => 'susp@co.test',
            'password' => 'password',
        ])->assertRedirect()
            ->assertSessionHasErrors('login');

        $this->assertGuest('company');
    }

    public function test_existing_session_blocked_when_company_becomes_inactive(): void
    {
        $company = Company::query()->create([
            'name' => 'Live Co',
            'status' => CompanyStatus::Active,
        ]);
        $user = CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'Owner',
            'email' => 'live@co.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => CompanyUserRole::Owner,
            'theme' => Theme::Light,
        ]);

        $this->actingAs($user, 'company')
            ->get(route('company.dashboard'))
            ->assertOk();

        $company->update(['status' => CompanyStatus::Inactive]);

        $this->actingAs($user->fresh(), 'company')
            ->get(route('company.dashboard'))
            ->assertRedirect(route('company.login'));
    }

    public function test_inactive_center_cannot_login_to_web_panel(): void
    {
        $center = Center::query()->create([
            'name' => 'Off Center',
            'type' => CenterType::Owned,
            'status' => CenterStatus::Inactive,
            'city' => 'Riyadh',
        ]);
        CenterUser::query()->create([
            'center_id' => $center->id,
            'name' => 'Mgr',
            'email' => 'off@center.test',
            'password' => 'password',
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $this->post(route('center.login.store'), [
            'login' => 'off@center.test',
            'password' => 'password',
        ])->assertRedirect()
            ->assertSessionHasErrors('login');

        $this->assertGuest('center');
    }
}
