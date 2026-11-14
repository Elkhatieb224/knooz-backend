<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Enums\CompanyStatus;
use App\Enums\ComplaintStatus;
use App\Enums\EmergencyRequestStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationImageType;
use App\Enums\OperationPaymentStatus;
use App\Enums\OperationStatus;
use App\Enums\PackageType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleStatus;
use App\Enums\WalletTransactionType;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Complaint;
use App\Models\EmergencyRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Offer;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $superAdmin = AdminUser::query()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@knooz.test',
            'phone' => '0500000000',
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $operationsManager = AdminUser::query()->create([
            'name' => 'مدير التشغيل',
            'email' => 'operations@knooz.test',
            'phone' => '0500000001',
            'password' => $password,
            'role' => UserRole::OperationsManager,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $accountant = AdminUser::query()->create([
            'name' => 'المحاسب',
            'email' => 'accountant@knooz.test',
            'phone' => '0500000002',
            'password' => $password,
            'role' => UserRole::Accountant,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $support = AdminUser::query()->create([
            'name' => 'فني الدعم',
            'email' => 'support@knooz.test',
            'phone' => '0500000003',
            'password' => $password,
            'role' => UserRole::Support,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $services = collect([
            [
                'name_ar' => 'غسيل',
                'name_en' => 'Car Wash',
                'slug' => 'car-wash',
                'icon' => 'droplet',
                'description' => 'خدمة غسيل السيارات الداخلية والخارجية',
                'base_price' => 50,
            ],
            [
                'name_ar' => 'تغيير زيت',
                'name_en' => 'Oil Change',
                'slug' => 'oil-change',
                'icon' => 'oil',
                'description' => 'تغيير زيت المحرك والفلاتر',
                'base_price' => 120,
            ],
            [
                'name_ar' => 'كفرات',
                'name_en' => 'Tires',
                'slug' => 'tires',
                'icon' => 'circle',
                'description' => 'صيانة وتغيير الإطارات',
                'base_price' => 200,
            ],
            [
                'name_ar' => 'طوارئ كفرات',
                'name_en' => 'Tire Emergency',
                'slug' => 'tire-emergency',
                'icon' => 'alert',
                'description' => 'خدمة طوارئ إصلاح أو تغيير الكفرات على الطريق',
                'base_price' => 250,
            ],
        ])->map(fn (array $data) => Service::query()->create([
            ...$data,
            'is_active' => true,
        ]));

        $wash = $services->firstWhere('slug', 'car-wash');
        $oil = $services->firstWhere('slug', 'oil-change');
        $tires = $services->firstWhere('slug', 'tires');

        $ownedCenter = Center::query()->create([
            'name' => 'مركز كنوز الرياض',
            'type' => CenterType::Owned,
            'phone' => '0112345678',
            'email' => 'riyadh@knooz.test',
            'address' => 'طريق الملك فهد',
            'city' => 'الرياض',
            'lat' => 24.7136000,
            'lng' => 46.6753000,
            'status' => CenterStatus::Active,
            'manager_id' => null,
        ]);

        $centerUser = CenterUser::query()->create([
            'center_id' => $ownedCenter->id,
            'name' => 'مسؤول مركز الرياض',
            'email' => 'center@knooz.test',
            'phone' => '0500000010',
            'password' => $password,
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        $ownedCenter->update(['manager_center_user_id' => $centerUser->id]);

        $contractedCenter = Center::query()->create([
            'name' => 'مركز النخبة جدة',
            'type' => CenterType::Contracted,
            'phone' => '0123456789',
            'email' => 'jeddah@partner.test',
            'address' => 'حي الروضة',
            'city' => 'جدة',
            'lat' => 21.5433000,
            'lng' => 39.1728000,
            'status' => CenterStatus::Active,
        ]);

        $branchEmployee = User::query()->create([
            'name' => 'موظف الفرع',
            'email' => 'employee@knooz.test',
            'phone' => '0500000011',
            'password' => $password,
            'role' => UserRole::BranchEmployee,
            'status' => UserStatus::Active,
            'center_id' => $ownedCenter->id,
            'email_verified_at' => now(),
        ]);

        $emergencyEmployee = User::query()->create([
            'name' => 'موظف الطوارئ',
            'email' => 'emergency@knooz.test',
            'phone' => '0500000012',
            'password' => $password,
            'role' => UserRole::EmergencyEmployee,
            'status' => UserStatus::Active,
            'center_id' => $ownedCenter->id,
            'email_verified_at' => now(),
        ]);

        foreach ([$ownedCenter, $contractedCenter] as $center) {
            $isContracted = $center->type === CenterType::Contracted;

            foreach ($services as $service) {
                $center->services()->attach($service->id, [
                    'contract_price' => $isContracted ? $service->base_price * 0.8 : $service->base_price,
                    'app_profit' => $isContracted ? $service->base_price * 0.2 : 0,
                    'is_available' => true,
                ]);
            }
        }

        $individualCustomer = User::query()->create([
            'name' => 'أحمد العميل',
            'email' => 'ahmed@customer.test',
            'phone' => '0551111111',
            'password' => $password,
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
            'qr_code' => 'QR-USER-AHMED',
            'nfc_code' => 'NFC-USER-AHMED',
            'email_verified_at' => now(),
        ]);

        $individualCustomer2 = User::query()->create([
            'name' => 'سارة العميل',
            'email' => 'sara@customer.test',
            'phone' => '0552222222',
            'password' => $password,
            'role' => UserRole::CustomerIndividual,
            'status' => UserStatus::Active,
            'qr_code' => 'QR-USER-SARA',
            'nfc_code' => 'NFC-USER-SARA',
            'email_verified_at' => now(),
        ]);

        $company = Company::query()->create([
            'name' => 'شركة النقل السريع',
            'commercial_register' => 'CR-1234567890',
            'phone' => '0119988776',
            'email' => 'fleet@fasttransport.test',
            'status' => CompanyStatus::Active,
            'credit_limit' => 20000,
            'usage_cap' => 15000,
            'payment_mode' => PaymentMode::Credit,
            'notes' => 'عميل شركات تجريبي',
        ]);

        $companyUser = CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'مسؤول الشركة',
            'email' => 'company@knooz.test',
            'phone' => '0553333333',
            'password' => $password,
            'status' => UserStatus::Active,
            'role' => \App\Enums\CompanyUserRole::Owner,
            'theme' => Theme::Light,
        ]);

        CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'مدير العمليات',
            'email' => 'manager@knooz.test',
            'phone' => '0553333335',
            'password' => $password,
            'status' => UserStatus::Active,
            'role' => \App\Enums\CompanyUserRole::Manager,
            'theme' => Theme::Light,
            'created_by' => $companyUser->id,
        ]);

        CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'محاسب الشركة',
            'email' => 'accountant@knooz.test',
            'phone' => '0553333336',
            'password' => $password,
            'status' => UserStatus::Active,
            'role' => \App\Enums\CompanyUserRole::Accountant,
            'theme' => Theme::Light,
            'created_by' => $companyUser->id,
        ]);

        CompanyUser::query()->create([
            'company_id' => $company->id,
            'name' => 'موظف مشاهدة',
            'email' => 'viewer@knooz.test',
            'phone' => '0553333337',
            'password' => $password,
            'status' => UserStatus::Active,
            'role' => \App\Enums\CompanyUserRole::Viewer,
            'theme' => Theme::Light,
            'created_by' => $companyUser->id,
        ]);

        $companyContact = User::query()->create([
            'name' => 'جهة اتصال الأسطول',
            'email' => 'fleet-contact@knooz.test',
            'phone' => '0553333334',
            'password' => $password,
            'role' => UserRole::CustomerCompany,
            'status' => UserStatus::Active,
            'company_id' => $company->id,
            'qr_code' => 'QR-COMPANY-CONTACT',
            'nfc_code' => 'NFC-COMPANY-CONTACT',
            'email_verified_at' => now(),
        ]);

        $company->users()->attach($companyContact->id, ['role' => 'admin']);

        $vehicleAhmed = Vehicle::query()->create([
            'user_id' => $individualCustomer->id,
            'plate_number' => 'أ ب ج 1234',
            'brand' => 'تويوتا',
            'model' => 'كامري',
            'color' => 'أبيض',
            'year' => 2022,
            'qr_code' => 'QR-VEH-AHMED-1',
            'nfc_code' => 'NFC-VEH-AHMED-1',
            'status' => VehicleStatus::Active,
        ]);

        $vehicleSara = Vehicle::query()->create([
            'user_id' => $individualCustomer2->id,
            'plate_number' => 'د هـ و 5678',
            'brand' => 'هيونداي',
            'model' => 'النترا',
            'color' => 'رمادي',
            'year' => 2021,
            'qr_code' => 'QR-VEH-SARA-1',
            'nfc_code' => 'NFC-VEH-SARA-1',
            'status' => VehicleStatus::Active,
        ]);

        $companyVehicle1 = Vehicle::query()->create([
            'company_id' => $company->id,
            'plate_number' => 'ش ر ك 1001',
            'brand' => 'نيسان',
            'model' => 'باترول',
            'color' => 'أسود',
            'year' => 2023,
            'qr_code' => 'QR-VEH-CO-1',
            'nfc_code' => 'NFC-VEH-CO-1',
            'status' => VehicleStatus::Active,
        ]);

        $companyVehicle2 = Vehicle::query()->create([
            'company_id' => $company->id,
            'plate_number' => 'ش ر ك 1002',
            'brand' => 'فورد',
            'model' => 'إكسبلورر',
            'color' => 'أزرق',
            'year' => 2020,
            'qr_code' => 'QR-VEH-CO-2',
            'nfc_code' => 'NFC-VEH-CO-2',
            'status' => VehicleStatus::Active,
        ]);

        $individualPackage = Package::query()->create([
            'name' => 'باقة الأفراد الأساسية',
            'type' => PackageType::Individual,
            'duration_days' => 30,
            'operations_count' => 8,
            'vehicles_count' => 1,
            'price' => 299,
            'discount_percent' => 10,
            'free_services' => [$wash->id],
            'is_active' => true,
        ]);

        $companyPackage = Package::query()->create([
            'name' => 'باقة الشركات المتقدمة',
            'type' => PackageType::Company,
            'duration_days' => 90,
            'operations_count' => 100,
            'vehicles_count' => 20,
            'price' => 4999,
            'discount_percent' => 15,
            'free_services' => [$wash->id, $oil->id],
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'user_id' => $individualCustomer->id,
            'package_id' => $individualPackage->id,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'operations_used' => 1,
            'status' => SubscriptionStatus::Active,
        ]);

        Subscription::query()->create([
            'company_id' => $company->id,
            'package_id' => $companyPackage->id,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(80),
            'operations_used' => 3,
            'status' => SubscriptionStatus::Active,
        ]);

        $booking = Booking::query()->create([
            'user_id' => $individualCustomer->id,
            'vehicle_id' => $vehicleAhmed->id,
            'center_id' => $ownedCenter->id,
            'service_id' => $wash->id,
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'status' => BookingStatus::New,
            'notes' => 'يفضل غسيل كامل',
            'price' => $wash->base_price,
        ]);

        Booking::query()->create([
            'user_id' => $individualCustomer2->id,
            'vehicle_id' => $vehicleSara->id,
            'center_id' => $contractedCenter->id,
            'service_id' => $oil->id,
            'scheduled_at' => now()->addDays(2)->setTime(14, 30),
            'status' => BookingStatus::InProgress,
            'price' => $oil->base_price,
        ]);

        $operation = Operation::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $individualCustomer->id,
            'vehicle_id' => $vehicleAhmed->id,
            'center_id' => $ownedCenter->id,
            'service_id' => $wash->id,
            'employee_id' => $branchEmployee->id,
            'status' => OperationStatus::Completed,
            'amount' => $wash->base_price,
            'payment_status' => OperationPaymentStatus::Paid,
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
            'notes' => 'تمت العملية بنجاح',
        ]);

        OperationImage::query()->create([
            'operation_id' => $operation->id,
            'path' => 'operations/sample-before.jpg',
            'type' => OperationImageType::Before,
        ]);

        OperationImage::query()->create([
            'operation_id' => $operation->id,
            'path' => 'operations/sample-after.jpg',
            'type' => OperationImageType::After,
        ]);

        Operation::query()->create([
            'user_id' => $companyContact->id,
            'vehicle_id' => $companyVehicle1->id,
            'center_id' => $ownedCenter->id,
            'service_id' => $tires->id,
            'employee_id' => $branchEmployee->id,
            'status' => OperationStatus::Completed,
            'amount' => $tires->base_price,
            'payment_status' => OperationPaymentStatus::Credit,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addHour(),
        ]);

        $customerWallet = Wallet::query()->create([
            'user_id' => $individualCustomer->id,
            'balance' => 150,
        ]);

        Wallet::query()->create([
            'company_id' => $company->id,
            'balance' => 5000,
        ]);

        WalletTransaction::query()->create([
            'wallet_id' => $customerWallet->id,
            'type' => WalletTransactionType::Credit,
            'amount' => 200,
            'balance_after' => 200,
            'description' => 'شحن رصيد أولي',
            'created_by' => null,
        ]);

        WalletTransaction::query()->create([
            'wallet_id' => $customerWallet->id,
            'type' => WalletTransactionType::Debit,
            'amount' => 50,
            'balance_after' => 150,
            'reference_type' => Operation::class,
            'reference_id' => $operation->id,
            'description' => 'خصم عملية غسيل',
            'created_by' => null,
        ]);

        Payment::query()->create([
            'user_id' => $individualCustomer->id,
            'wallet_id' => $customerWallet->id,
            'amount' => 200,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Approved,
            'receipt_path' => 'receipts/sample-transfer.pdf',
            'reviewed_by' => null,
            'reviewed_at' => now()->subDay(),
            'notes' => 'تم قبول التحويل',
        ]);

        Payment::query()->create([
            'company_id' => $company->id,
            'amount' => 1500,
            'method' => PaymentMethod::Online,
            'status' => PaymentStatus::Paid,
            'notes' => 'دفع إلكتروني',
        ]);

        Payment::query()->create([
            'user_id' => $individualCustomer2->id,
            'amount' => 120,
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'receipt_path' => 'receipts/pending-transfer.jpg',
            'notes' => 'بانتظار المراجعة',
        ]);

        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'number' => 'INV-'.now()->format('Ym').'-001',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'total' => 200,
            'status' => InvoiceStatus::Issued,
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'description' => 'خدمة كفرات - باترول',
            'vehicle_id' => $companyVehicle1->id,
            'service_id' => $tires->id,
            'amount' => 200,
        ]);

        Complaint::query()->create([
            'user_id' => $individualCustomer->id,
            'subject' => 'تأخير في الموعد',
            'body' => 'تأخر تنفيذ الحجز عن الموعد المحدد بساعة.',
            'status' => ComplaintStatus::Open,
            'assigned_to' => null,
        ]);

        Complaint::query()->create([
            'user_id' => $individualCustomer2->id,
            'subject' => 'جودة الغسيل',
            'body' => 'الجودة لم تكن بالمستوى المتوقع.',
            'status' => ComplaintStatus::InProgress,
            'assigned_to' => null,
            'admin_notes' => 'تم التواصل مع المركز',
        ]);

        EmergencyRequest::query()->create([
            'user_id' => $individualCustomer->id,
            'vehicle_id' => $vehicleAhmed->id,
            'lat' => 24.7200000,
            'lng' => 46.6800000,
            'address' => 'طريق المطار، الرياض',
            'status' => EmergencyRequestStatus::Assigned,
            'employee_id' => $emergencyEmployee->id,
            'notes' => 'ثقب في الإطار الأمامي',
        ]);

        Offer::query()->create([
            'title' => 'خصم الغسيل الأسبوعي',
            'description' => 'خصم خاص على خدمة الغسيل طوال الأسبوع',
            'discount_percent' => 20,
            'service_id' => $wash->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(14),
            'is_active' => true,
        ]);

        Offer::query()->create([
            'title' => 'عرض تغيير الزيت',
            'description' => 'خصم على تغيير الزيت لفترة محدودة',
            'discount_percent' => 15,
            'service_id' => $oil->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $settings = [
            'app_name' => 'كنوز',
            'app_name_en' => 'KNOOZ',
            'support_phone' => '920000000',
            'support_email' => 'support@knooz.test',
            'currency' => 'SAR',
            'default_theme' => 'light',
            'vat_percent' => '15',
            'emergency_radius_km' => '25',
        ];

        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        $notificationService = app(\App\Services\Notifications\NotificationService::class);

        $notificationService->storeMany(
            collect([$superAdmin, $operationsManager, $accountant, $support]),
            \App\Enums\NotificationType::System,
            'مرحباً بك في كنوز',
            'تم تجهيز لوحة التحكم بنجاح. راجع الحجوزات والمدفوعات المعلقة.',
            eventKey: 'seed:admin:welcome',
        );

        $notificationService->storeMany(
            collect([$superAdmin, $operationsManager, $accountant, $support]),
            \App\Enums\NotificationType::PaymentPending,
            'تحويل بنكي بانتظار المراجعة',
            'يوجد مستند تحويل يحتاج موافقة المحاسب.',
            eventKey: 'seed:admin:payment-pending',
        );

        if (isset($companyUser)) {
            $notificationService->storeMany(
                collect([$companyUser]),
                \App\Enums\NotificationType::System,
                'مرحباً بك في لوحة الشركة',
                'يمكنك متابعة المركبات والعمليات والفواتير من هنا.',
                eventKey: 'seed:company:welcome',
            );
        }

        if (isset($centerUser)) {
            $notificationService->storeMany(
                collect([$centerUser]),
                \App\Enums\NotificationType::System,
                'مرحباً بك في لوحة المركز',
                'تابع الحجوزات والعمليات اليومية من لوحة المركز.',
                eventKey: 'seed:center:welcome',
            );
        }

        foreach ([
            ['name_ar' => 'إطار ميشلان 18', 'slug' => 'michelin-18', 'category' => 'tires', 'brand' => 'Michelin', 'size' => '18', 'price' => 450],
            ['name_ar' => 'إطار بريدجستون 17', 'slug' => 'bridgestone-17', 'category' => 'tires', 'brand' => 'Bridgestone', 'size' => '17', 'price' => 380],
            ['name_ar' => 'زيت موبيل 5W-30', 'slug' => 'mobil-5w30', 'category' => 'oils', 'brand' => 'Mobil', 'size' => '4L', 'price' => 145],
            ['name_ar' => 'زيت كاسترول 5W-40', 'slug' => 'castrol-5w40', 'category' => 'oils', 'brand' => 'Castrol', 'size' => '4L', 'price' => 160],
        ] as $product) {
            \App\Models\ShopProduct::query()->create([
                ...$product,
                'stock' => 25,
                'is_active' => true,
                'description' => $product['name_ar'],
            ]);
        }

        // Seed notifications for sample customers if present.
        $customers = User::query()
            ->whereIn('role', [
                \App\Enums\UserRole::CustomerIndividual->value,
                \App\Enums\UserRole::CustomerCompany->value,
            ])
            ->get();

        if ($customers->isNotEmpty()) {
            $notificationService->storeMany(
                $customers,
                \App\Enums\NotificationType::System,
                'مرحباً بك في تطبيق كنوز',
                'يمكنك حجز الخدمات ومتابعة محفظتك من هنا.',
                eventKey: 'seed:customers:welcome',
            );
        }
    }
}
