<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }

    if (Auth::guard('company')->check()) {
        return redirect()->route('company.dashboard');
    }

    if (Auth::guard('center')->check()) {
        return redirect()->route('center.dashboard');
    }

    return redirect()->route('admin.login');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
