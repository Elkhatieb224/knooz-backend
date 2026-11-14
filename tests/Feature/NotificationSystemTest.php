<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CompanyStatus;
use App\Enums\NotificationType;
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
use App\Models\DatabaseNotification;
use App\Models\PanelApiToken;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_preview_and_open_notification(): void
    {
        $admin = $this->makeAdmin();
        $service = app(NotificationService::class);
        $service->storeMany(
            collect([$admin]),
            NotificationType::PaymentPending,
            'دفعة معلقة',
            'راجع التحويل البنكي',
            eventKey: 'test:admin:payment',
        );

        $notification = $admin->notifications()->first();

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.notifications.preview'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'items');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $adminA = $this->makeAdmin('a@knooz.test');
        $adminB = $this->makeAdmin('b@knooz.test');

        app(NotificationService::class)->storeMany(
            collect([$adminA]),
            NotificationType::System,
            'خاص',
            'لا يراه غير المستلم',
            eventKey: 'test:idor',
        );

        $notification = $adminA->notifications()->first();

        $this->actingAs($adminB, 'admin')
            ->get(route('admin.notifications.open', $notification->id))
            ->assertNotFound();
    }

    public function test_company_and_center_have_isolated_notifications(): void
    {
        $company = $this->makeCompany('Co');
        $companyUser = $this->makeCompanyUser($company, 'co@knooz.test');
        $center = $this->makeCenter('Ce');
        $centerUser = $this->makeCenterUser($center, 'ce@knooz.test');

        $service = app(NotificationService::class);
        $service->storeMany(collect([$companyUser]), NotificationType::InvoiceIssued, 'فاتورة', 'صدرت فاتورة', eventKey: 'test:co');
        $service->storeMany(collect([$centerUser]), NotificationType::BookingNew, 'حجز', 'حجز مرتبط بالمركز', eventKey: 'test:ce');

        $this->actingAs($companyUser, 'company')
            ->get(route('company.notifications.index'))
            ->assertOk()
            ->assertSee('صدرت فاتورة')
            ->assertDontSee('حجز مرتبط بالمركز');

        $this->actingAs($centerUser, 'center')
            ->get(route('center.notifications.index'))
            ->assertOk()
            ->assertSee('حجز مرتبط بالمركز')
            ->assertDontSee('صدرت فاتورة');
    }

    public function test_mark_all_read_is_scoped_and_efficient(): void
    {
        $admin = $this->makeAdmin();
        $service = app(NotificationService::class);
        $service->storeMany(collect([$admin]), NotificationType::System, 'أ', '1', eventKey: 'm1');
        $service->storeMany(collect([$admin]), NotificationType::System, 'ب', '2', eventKey: 'm2');

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $admin->notifications()->unread()->count());
    }

    public function test_event_key_prevents_duplicate_notifications(): void
    {
        $admin = $this->makeAdmin();
        $service = app(NotificationService::class);

        $this->assertSame(1, $service->storeMany(collect([$admin]), NotificationType::System, 'مرة', 'واحدة', eventKey: 'dup'));
        $this->assertSame(0, $service->storeMany(collect([$admin]), NotificationType::System, 'مرة', 'واحدة', eventKey: 'dup'));
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_api_token_can_list_and_open_notifications(): void
    {
        $admin = $this->makeAdmin();
        app(NotificationService::class)->storeMany(
            collect([$admin]),
            NotificationType::CompanyCreated,
            'شركة',
            'تم إنشاء شركة',
            eventKey: 'api:1',
        );

        $issued = PanelApiToken::issue($admin, 'admin', 'test');
        $notification = $admin->notifications()->first();

        $this->withToken($issued['plain'])
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->withToken($issued['plain'])
            ->getJson('/api/notifications/'.$notification->id.'/open')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_booking_update_notifies_center_and_admins(): void
    {
        $admin = $this->makeAdmin();
        $center = $this->makeCenter('Center');
        $centerUser = $this->makeCenterUser($center, 'center@knooz.test');

        $customer = User::query()->create([
            'name' => 'Customer',
            'email' => 'customer@test.com',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
        ]);

        $svc = Service::query()->create([
            'name_ar' => 'غسيل',
            'name_en' => 'Wash',
            'slug' => 'wash-notif',
            'base_price' => 50,
            'is_active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'user_id' => $customer->id,
            'plate_number' => 'N-100',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'status' => VehicleStatus::Active,
            'qr_code' => 'QR-N-1',
            'nfc_code' => 'NFC-N-1',
        ]);

        $booking = Booking::query()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $svc->id,
            'scheduled_at' => now()->addDay(),
            'status' => BookingStatus::New,
            'price' => 50,
        ]);

        $this->actingAs($centerUser, 'center')
            ->patch(route('center.bookings.update', $booking), [
                'status' => BookingStatus::Completed->value,
            ])
            ->assertRedirect();

        $this->assertTrue($admin->notifications()->where('notification_type', NotificationType::BookingStatus->value)->exists());
        $this->assertTrue($centerUser->notifications()->where('notification_type', NotificationType::BookingStatus->value)->exists());
    }

    protected function makeAdmin(string $email = 'admin@knooz.test'): AdminUser
    {
        return AdminUser::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }

    protected function makeCompany(string $name): Company
    {
        return Company::query()->create([
            'name' => $name,
            'commercial_register' => 'CR-'.$name,
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
