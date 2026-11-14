<?php

use App\Http\Controllers\Center\Auth\LoginController as CenterLoginController;
use App\Http\Controllers\Center\BookingController;
use App\Http\Controllers\Center\DashboardController;
use App\Http\Controllers\Center\NotificationController;
use App\Http\Controllers\Center\OperationController;
use App\Http\Controllers\Center\ProfileController;
use App\Http\Controllers\Center\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('center')->name('center.')->group(function () {
    Route::middleware('guest:center')->group(function () {
        Route::get('login', [CenterLoginController::class, 'show'])->name('login');
        Route::post('login', [CenterLoginController::class, 'store'])->name('login.store');
    });

    Route::post('logout', [CenterLoginController::class, 'destroy'])
        ->middleware('auth:center')
        ->name('logout');

    Route::middleware(['auth:center', 'center'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::patch('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');

        Route::get('operations', [OperationController::class, 'index'])->name('operations.index');
        Route::patch('operations/{operation}', [OperationController::class, 'update'])->name('operations.update');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('theme', [ProfileController::class, 'updateTheme'])->name('theme');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/preview', [NotificationController::class, 'preview'])->name('notifications.preview');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
});
