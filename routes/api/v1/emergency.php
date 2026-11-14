<?php

use App\Http\Controllers\Api\V1\EmergencyController;
use Illuminate\Support\Facades\Route;

Route::middleware('customer.api')->group(function () {
    Route::get('emergency', [EmergencyController::class, 'index']);
    Route::post('emergency', [EmergencyController::class, 'store']);
    Route::get('emergency/{emergency}', [EmergencyController::class, 'show']);
});
