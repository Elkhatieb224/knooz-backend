<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\PaymentMode;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLaunchCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_pages_admin_and_public_api(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.content.update', 'privacy'), [
                'title' => 'سياسة الخصوصية',
                'body' => '<p>نص الخصوصية المحدث</p>',
            ])
            ->assertRedirect(route('admin.content.index'));

        $this->getJson('/api/v1/content/privacy')
            ->assertOk()
            ->assertJsonPath('data.title', 'سياسة الخصوصية')
            ->assertJsonPath('data.body', '<p>نص الخصوصية المحدث</p>');
    }

    public function test_company_approve_and_reject_with_reason(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::query()->create([
            'name' => 'شركة مراجعة',
            'email' => 'review-co@knooz.test',
            'phone' => '0556000111',
            'status' => CompanyStatus::Pending,
            'payment_mode' => PaymentMode::cases()[0],
            'usage_cap' => 1000,
        ]);
        $member = CompanyUser::query()->forceCreate([
            'company_id' => $company->id,
            'name' => 'مدير شركة',
            'email' => 'review-owner@knooz.test',
            'phone' => '0556000222',
            'password' => 'password',
            'role' => 'owner',
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.companies.approve', $company), ['notes' => 'مستندات مكتملة'])
            ->assertRedirect();

        $this->assertSame(CompanyStatus::Active, $company->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => CompanyUser::class,
            'notifiable_id' => $member->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.companies.reject', $company), ['notes' => 'مخالفة اشتراك'])
            ->assertRedirect();

        $this->assertSame(CompanyStatus::Suspended, $company->fresh()->status);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'companies.reject',
        ]);
    }

    public function test_broadcast_notification_requires_manage_permission(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.notifications.broadcast'))
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.notifications.broadcast.store'), [
                'audience' => 'admins',
                'title' => 'تنبيه تشغيلي',
                'body' => 'رسالة اختبار جماعية',
            ])
            ->assertRedirect(route('admin.notifications.index'));
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::query()->forceCreate([
            'name' => 'مشرف إطلاق',
            'email' => 'launch-admin@knooz.test',
            'phone' => '0501777666',
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
