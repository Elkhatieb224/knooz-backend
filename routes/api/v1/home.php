<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('customer.api')->group(function () {
    Route::get('home', HomeController::class);
});

Route::get('services', [CatalogController::class, 'services']);
Route::get('centers', [CatalogController::class, 'centers']);
Route::get('centers/{center}', [CatalogController::class, 'showCenter']);
Route::get('offers', [CatalogController::class, 'offers']);
Route::get('packages', [CatalogController::class, 'packages']);
