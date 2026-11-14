<?php

use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('customer.api')->group(function () {
    Route::get('wallet', [WalletController::class, 'show']);
    Route::post('wallet/recharge', [WalletController::class, 'recharge']);
    Route::get('wallet/payments', [WalletController::class, 'payments']);
});
