<?php

use App\Http\Controllers\Api\Center\V1\AuthController;
use App\Http\Controllers\Api\Center\V1\BookingController;
use App\Http\Controllers\Api\Center\V1\ComplaintController;
use App\Http\Controllers\Api\Center\V1\CustomerController;
use App\Http\Controllers\Api\Center\V1\DashboardController;
use App\Http\Controllers\Api\Center\V1\NotificationController;
use App\Http\Controllers\Api\Center\V1\OperationController;
use App\Http\Controllers\Api\Center\V1\ReportController;
use App\Http\Controllers\Api\Center\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware(['panel.api', 'center.api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);

    Route::get('dashboard', DashboardController::class);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/{id}', [BookingController::class, 'show'])->whereNumber('id');
    Route::patch('bookings/{id}', [BookingController::class, 'update'])->whereNumber('id');

    Route::get('operations', [OperationController::class, 'index']);
    Route::post('operations/lookup', [OperationController::class, 'lookup'])->middleware('throttle:30,1');
    Route::post('operations', [OperationController::class, 'store']);
    Route::get('operations/{id}', [OperationController::class, 'show'])->whereNumber('id');
    Route::patch('operations/{id}', [OperationController::class, 'update'])->whereNumber('id');
    Route::post('operations/{id}/images', [OperationController::class, 'storeImage'])->whereNumber('id');

    Route::get('reports/summary', [ReportController::class, 'summary']);

    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{userId}', [CustomerController::class, 'show'])->whereNumber('userId');

    Route::get('complaints', [ComplaintController::class, 'index']);
    Route::post('complaints', [ComplaintController::class, 'store']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('notifications/{id}/open', [NotificationController::class, 'open']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::patch('services/{serviceId}', [ServiceController::class, 'update'])->whereNumber('serviceId');
});
