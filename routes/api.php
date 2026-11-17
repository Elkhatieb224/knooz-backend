<?php

use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Middleware\AuthenticatePanelApiToken;
use Illuminate\Support\Facades\Route;

/*
| Legacy notification API (panel + mobile tokens via panel_api_tokens).
| Kept for backward compatibility with earlier integrations/tests.
*/
Route::middleware([AuthenticatePanelApiToken::class, 'throttle:60,1'])->group(function () {
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationApiController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationApiController::class, 'markRead']);
    Route::get('notifications/{id}/open', [NotificationApiController::class, 'open']);
    Route::post('device-tokens', [NotificationApiController::class, 'registerDevice']);
});

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/home.php';
    require __DIR__.'/api/v1/vehicles.php';
    require __DIR__.'/api/v1/bookings.php';
    require __DIR__.'/api/v1/wallet.php';
    require __DIR__.'/api/v1/emergency.php';
    require __DIR__.'/api/v1/home_visits.php';
    require __DIR__.'/api/v1/shop.php';
    require __DIR__.'/api/v1/notifications.php';
});


Route::prefix('center/v1')->middleware('throttle:120,1')->group(function () {
    require __DIR__.'/api/center/v1.php';
});

Route::prefix('company/v1')->middleware('throttle:120,1')->group(function () {
    require __DIR__.'/api/company/v1.php';
});
