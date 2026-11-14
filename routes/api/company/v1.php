<?php

use App\Http\Controllers\Api\Company\V1\AuthController;
use App\Http\Controllers\Api\Company\V1\DashboardController;
use App\Http\Controllers\Api\Company\V1\InvoiceController;
use App\Http\Controllers\Api\Company\V1\NotificationController;
use App\Http\Controllers\Api\Company\V1\OperationController;
use App\Http\Controllers\Api\Company\V1\ReportController;
use App\Http\Controllers\Api\Company\V1\StaffController;
use App\Http\Controllers\Api\Company\V1\SubscriptionController;
use App\Http\Controllers\Api\Company\V1\VehicleController;
use App\Http\Controllers\Api\Company\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware(['panel.api', 'company.api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile'])
        ->middleware('company.can:profile.edit');

    Route::get('dashboard', DashboardController::class)
        ->middleware('company.can:dashboard.view');

    Route::middleware('company.can:vehicles.view')->group(function () {
        Route::get('vehicles', [VehicleController::class, 'index']);
        Route::get('vehicles/{id}', [VehicleController::class, 'show'])->whereNumber('id');
    });

    Route::get('operations', [OperationController::class, 'index'])
        ->middleware('company.can:operations.view');

    Route::middleware('company.can:invoices.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::get('invoices/{id}', [InvoiceController::class, 'show'])->whereNumber('id');
    });

    Route::get('subscriptions', [SubscriptionController::class, 'index'])
        ->middleware('company.can:subscription.view');

    Route::get('wallet', WalletController::class)
        ->middleware('company.can:wallet.view');

    Route::get('reports/summary', [ReportController::class, 'summary'])
        ->middleware('company.can:reports.view');

    Route::middleware('company.can:staff.manage')->group(function () {
        Route::get('staff', [StaffController::class, 'index']);
        Route::post('staff', [StaffController::class, 'store']);
        Route::put('staff/{staff}', [StaffController::class, 'update'])->whereNumber('staff');
        Route::post('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->whereNumber('staff');
    });

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('notifications/{id}/open', [NotificationController::class, 'open']);
});
