<?php

use App\Http\Controllers\Api\V1\HomeVisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('customer.api')->group(function () {
    Route::post('home-visits', [HomeVisitController::class, 'store']);
});
