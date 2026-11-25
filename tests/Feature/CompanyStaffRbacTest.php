<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\PanelApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyStaffRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_staff_and_me_returns_permissions(): void
    {
        $company = $this->company();
        $owner = $this->user($company, 'owner@test.com', CompanyUserRole::Owner);
        $token = $this->token($owner);

        $this->withToken($token)
            ->getJson('/api/company/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'owner')
            ->assertJsonFragment(['staff.manage']);

        $this->withToken($token)
            ->postJson('/api/company/v1/staff', [
                'name' => 'مشاهد جديد',
                'email' => 'newviewer@test.com',
                'phone' => '0559000001',
                'role' => 'viewer',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'newviewer@test.com')
            ->assertJsonPath('data.role', 'viewer');

        $this->assertDatabaseHas('company_users', [
            'email' => 'newviewer@test.com',
            'company_id' => $company->id,
            'role' => 'viewer',
        ]);
    }

    public function test_viewer_cannot_manage_staff_or_view_invoices(): void
    {
        $company = $this->company();
        $viewer = $this->user($company, 'viewer@test.com', CompanyUserRole::Viewer);
        $token = $this->token($viewer);

        $this->withToken($token)->getJson('/api/company/v1/staff')->assertForbidden();
        $this->withToken($token)->getJson('/api/company/v1/invoices')->assertForbidden();
        $this->withToken($token)->getJson('/api/company/v1/wallet')->assertForbidden();
        $this->withToken($token)->getJson('/api/company/v1/reports/summary')->assertForbidden();
        $this->withToken($token)->getJson('/api/company/v1/vehicles')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/dashboard')->assertOk();
    }

    public function test_accountant_can_view_finance_but_not_staff(): void
    {
        $company = $this->company();
        $accountant = $this->user($company, 'acc@test.com', CompanyUserRole::Accountant);
        $token = $this->token($accountant);

        $this->withToken($token)->getJson('/api/company/v1/invoices')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/wallet')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/reports/summary')->assertOk();
        $this->withToken($token)->getJson('/api/company/v1/staff')->assertForbidden();
        $this->withToken($token)->getJson('/api/company/v1/subscriptions')->assertForbidden();
    }

    public function test_manager_cannot_assign_owner_role(): void
    {
        $company = $this->company();
        $manager = $this->user($company, 'mgr@test.com', CompanyUserRole::Manager);
        $token = $this->token($manager);

        $this->withToken($token)
            ->postJson('/api/company/v1/staff', [
                'name' => 'محاولة مالك',
                'email' => 'fakeowner@test.com',
                'phone' => '0559000002',
                'role' => 'owner',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertForbidden();
    }

    public function test_toggle_status_revokes_token_and_blocks_login(): void
    {
        $company = $this->company();
        $owner = $this->user($company, 'owner2@test.com', CompanyUserRole::Owner);
        $staff = $this->user($company, 'staff2@test.com', CompanyUserRole::Viewer, '0559000003');
        $staffToken = $this->token($staff);
        $ownerToken = $this->token($owner);

        $this->withToken($ownerToken)
            ->postJson('/api/company/v1/staff/'.$staff->id.'/toggle-status')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withToken($staffToken)->getJson('/api/company/v1/auth/me')->assertStatus(401);

        $this->postJson('/api/company/v1/auth/login', [
            'login' => 'staff2@test.com',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_cannot_manage_other_company_staff(): void
    {
        $companyA = $this->company('A');
        $companyB = $this->company('B');
        $ownerA = $this->user($companyA, 'a@test.com', CompanyUserRole::Owner);
        $staffB = $this->user($companyB, 'b@test.com', CompanyUserRole::Viewer, '0559000004');
        $token = $this->token($ownerA);

        $this->withToken($token)
            ->putJson('/api/company/v1/staff/'.$staffB->id, [
                'name' => 'Hacked',
                'email' => 'b@test.com',
                'phone' => '0559000004',
                'role' => 'viewer',
            ])
            ->assertNotFound();
    }

    public function test_web_viewer_cannot_open_staff_page(): void
    {
        $company = $this->company();
        $viewer = $this->user($company, 'webviewer@test.com', CompanyUserRole::Viewer);

        $this->actingAs($viewer, 'company')
            ->get(route('company.staff.index'))
            ->assertForbidden();

        $this->actingAs($viewer, 'company')
            ->get(route('company.vehicles.index'))
            ->assertOk();
    }

    protected function company(string $suffix = '1'): Company
    {
        return Company::query()->create([
            'name' => 'Co '.$suffix,
            'status' => CompanyStatus::Active,
        ]);
    }

    protected function user(
        Company $company,
        string $email,
        CompanyUserRole $role,
        ?string $phone = null,
    ): CompanyUser {
        return CompanyUser::query()->forceCreate([
            'company_id' => $company->id,
            'name' => $email,
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'status' => UserStatus::Active,
            'role' => $role,
            'theme' => Theme::Light,
        ]);
    }

    protected function token(CompanyUser $user): string
    {
        return PanelApiToken::issue($user, 'company', 'company-app')['plain'];
    }
}
