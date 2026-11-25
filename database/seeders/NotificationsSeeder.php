<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\AdminUser;
use App\Models\CenterUser;
use App\Models\CompanyUser;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Seeder;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = app(NotificationService::class);

        $admins = AdminUser::query()
            ->whereIn('email', [
                'admin@knooz.test',
                'operations@knooz.test',
                'accountant@knooz.test',
                'support@knooz.test',
            ])
            ->get();

        if ($admins->isNotEmpty()) {
            $notifications->storeMany(
                $admins,
                NotificationType::System,
                'مرحباً بك في كنوز',
                'تم تجهيز لوحة التحكم بنجاح. راجع الحجوزات والمدفوعات المعلقة.',
                eventKey: 'seed:admin:welcome',
            );

            $pendingPayment = Payment::query()
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            $notifications->storeMany(
                $admins,
                NotificationType::PaymentPending,
                'تحويل بنكي بانتظار المراجعة',
                $pendingPayment
                    ? "يوجد تحويل #{$pendingPayment->id} بمبلغ {$pendingPayment->amount} يحتاج موافقة المحاسب."
                    : 'يوجد مستند تحويل يحتاج موافقة المحاسب.',
                reference: $pendingPayment,
                eventKey: 'seed:admin:payment-pending',
            );
        }

        $companyOwner = CompanyUser::query()->where('email', 'company@knooz.test')->first();
        if ($companyOwner) {
            $notifications->storeMany(
                collect([$companyOwner]),
                NotificationType::System,
                'مرحباً بك في لوحة الشركة',
                'يمكنك متابعة المركبات والعمليات والفواتير من هنا.',
                eventKey: 'seed:company:welcome',
            );
        }

        $centerUser = CenterUser::query()->where('email', 'center@knooz.test')->first();
        if ($centerUser) {
            $notifications->storeMany(
                collect([$centerUser]),
                NotificationType::System,
                'مرحباً بك في لوحة المركز',
                'تابع الحجوزات والعمليات اليومية من لوحة المركز.',
                eventKey: 'seed:center:welcome',
            );
        }

        $customers = User::query()
            ->whereIn('role', [
                UserRole::CustomerIndividual->value,
                UserRole::CustomerCompany->value,
            ])
            ->get();

        if ($customers->isNotEmpty()) {
            $notifications->storeMany(
                $customers,
                NotificationType::System,
                'مرحباً بك في تطبيق كنوز',
                'يمكنك حجز الخدمات ومتابعة محفظتك من هنا.',
                eventKey: 'seed:customers:welcome',
            );
        }

        $this->command?->info('Notifications seeded.');
    }
}
