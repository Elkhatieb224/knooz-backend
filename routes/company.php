<?php

use App\Http\Controllers\Company\Auth\LoginController as CompanyLoginController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\InvoiceController;
use App\Http\Controllers\Company\NotificationController;
use App\Http\Controllers\Company\OperationController;
use App\Http\Controllers\Company\ProfileController;
use App\Http\Controllers\Company\StaffController;
use App\Http\Controllers\Company\SubscriptionController;
use App\Http\Controllers\Company\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->name('company.')->group(function () {
    Route::middleware('guest:company')->group(function () {
        Route::get('login', [CompanyLoginController::class, 'show'])->name('login');
        Route::post('login', [CompanyLoginController::class, 'store'])->name('login.store');
    });

    Route::post('logout', [CompanyLoginController::class, 'destroy'])
        ->middleware('auth:company')
        ->name('logout');

    Route::middleware(['auth:company', 'company'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('company.can:dashboard.view')
            ->name('dashboard');

        Route::middleware('company.can:vehicles.view')->group(function () {
            Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
            Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        });

        Route::get('operations', [OperationController::class, 'index'])
            ->middleware('company.can:operations.view')
            ->name('operations.index');

        Route::middleware('company.can:invoices.view')->group(function () {
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        });

        Route::get('subscription', [SubscriptionController::class, 'index'])
            ->middleware('company.can:subscription.view')
            ->name('subscription.index');

        Route::middleware('company.can:staff.manage')->group(function () {
            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
            Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
            Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
            Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
            Route::post('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        });

        Route::middleware('company.can:profile.edit')->group(function () {
            Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('theme', [ProfileController::class, 'updateTheme'])->name('theme');
        });

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/preview', [NotificationController::class, 'preview'])->name('notifications.preview');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
});
