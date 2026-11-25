<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPaymentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_show_page_and_receipt_download(): void
    {
        Storage::fake('public');

        $admin = $this->makeAdmin();
        $customer = User::query()->forceCreate([
            'name' => 'عميل إيصال',
            'email' => 'receipt-user@knooz.test',
            'phone' => '0558111222',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg', 600, 400);
        $path = $file->store('receipts/'.$customer->id, 'public');

        $payment = Payment::query()->create([
            'user_id' => $customer->id,
            'amount' => 175.5,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'receipt_path' => $path,
            'notes' => 'مرجع تحويل ABC-99',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('175.50')
            ->assertSee('مرجع تحويل ABC-99')
            ->assertSee('قبول التحويل')
            ->assertSee('رفض التحويل');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payments.receipt', ['payment' => $payment, 'download' => 1]))
            ->assertOk();
    }

    public function test_reject_requires_comment_and_notifies_customer(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::query()->forceCreate([
            'name' => 'عميل رفض',
            'email' => 'reject-user@knooz.test',
            'phone' => '0558333444',
            'password' => 'password',
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $customer->id,
            'amount' => 90,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'notes' => 'طلب أصلي',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payments.reject', $payment), [])
            ->assertSessionHasErrors('notes');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payments.reject', $payment), [
                'notes' => 'الإيصال غير واضح',
            ])
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $customer->id,
        ]);
        $this->assertStringContainsString('الإيصال غير واضح', (string) data_get(
            $customer->notifications()->latest()->first()?->data,
            'body'
        ));
    }

    private function makeAdmin(): AdminUser
    {
        return AdminUser::query()->forceCreate([
            'name' => 'محاسب',
            'email' => 'pay-admin@knooz.test',
            'phone' => '0501222333',
            'password' => 'password',
            'role' => UserRole::Accountant,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);
    }
}
