<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\BroadcastNotificationController;
use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\ShopProductController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RequestHubController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

    Route::post('logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    Route::middleware(['auth:admin', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');

        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/regenerate-codes', [UserController::class, 'regenerateCodes'])->name('users.regenerate-codes');

        Route::resource('companies', CompanyController::class);
        Route::post('companies/{company}/attach-vehicle', [CompanyController::class, 'attachVehicle'])->name('companies.attach-vehicle');
        Route::post('companies/{company}/approve', [CompanyController::class, 'approve'])->name('companies.approve');
        Route::post('companies/{company}/reject', [CompanyController::class, 'reject'])->name('companies.reject');

        Route::resource('centers', CenterController::class);

        Route::get('requests', [RequestHubController::class, 'index'])->name('requests.index');
        Route::patch('requests/bookings/{booking}', [RequestHubController::class, 'updateBooking'])->name('requests.bookings.update');
        Route::patch('requests/operations/{operation}', [RequestHubController::class, 'updateOperation'])->name('requests.operations.update');
        Route::patch('requests/complaints/{complaint}', [RequestHubController::class, 'updateComplaint'])->name('requests.complaints.update');
        Route::patch('requests/emergency/{emergencyRequest}', [RequestHubController::class, 'updateEmergency'])->name('requests.emergency.update');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

        Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');

        Route::resource('vehicles', VehicleController::class);
        Route::post('vehicles/{vehicle}/regenerate-codes', [VehicleController::class, 'regenerateCodes'])->name('vehicles.regenerate-codes');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('content', [ContentController::class, 'index'])->name('content.index');
        Route::get('content/{page}/edit', [ContentController::class, 'edit'])->name('content.edit');
        Route::put('content/{page}', [ContentController::class, 'update'])->name('content.update');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::resource('offers', OfferController::class);

        Route::resource('shop-products', ShopProductController::class);
        Route::post('shop-products/{shop_product}/toggle', [ShopProductController::class, 'toggleStatus'])
            ->name('shop-products.toggle');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings/general', [SettingController::class, 'updateGeneral'])->name('settings.general');
        Route::post('settings/services', [SettingController::class, 'storeService'])->name('settings.services.store');
        Route::put('settings/services/{service}', [SettingController::class, 'updateService'])->name('settings.services.update');
        Route::post('settings/services/{service}/toggle', [SettingController::class, 'toggleService'])->name('settings.services.toggle');
        Route::post('settings/packages', [SettingController::class, 'storePackage'])->name('settings.packages.store');
        Route::put('settings/packages/{package}', [SettingController::class, 'updatePackage'])->name('settings.packages.update');
        Route::delete('settings/packages/{package}', [SettingController::class, 'destroyPackage'])->name('settings.packages.destroy');
        Route::get('settings/profile', [SettingController::class, 'profile'])->name('settings.profile');
        Route::put('settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile.update');
        Route::post('settings/theme', [SettingController::class, 'updateTheme'])->name('settings.theme');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{admin}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{admin}', [RoleController::class, 'update'])->name('roles.update');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/broadcast', [BroadcastNotificationController::class, 'create'])->name('notifications.broadcast');
        Route::post('notifications/broadcast', [BroadcastNotificationController::class, 'store'])->name('notifications.broadcast.store');
        Route::get('notifications/preview', [NotificationController::class, 'preview'])->name('notifications.preview');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
});
