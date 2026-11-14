<?php

use App\Http\Controllers\Api\V1\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('shop/products', [ShopController::class, 'index']);
Route::get('shop/products/{product}', [ShopController::class, 'show']);
