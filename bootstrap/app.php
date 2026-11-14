<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCenter;
use App\Http\Middleware\EnsureCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/company.php'));
            Route::middleware('web')->group(base_path('routes/center.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'company' => EnsureCompany::class,
            'center' => EnsureCenter::class,
            'panel.api' => \App\Http\Middleware\AuthenticatePanelApiToken::class,
            'center.api' => \App\Http\Middleware\EnsureCenterApi::class,
            'company.can' => \App\Http\Middleware\EnsureCompanyPermission::class,
            'company.api' => \App\Http\Middleware\EnsureCompanyApi::class,
            'customer.api' => \App\Http\Middleware\AuthenticateCustomerApi::class,
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            if ($request->is('company') || $request->is('company/*')) {
                return route('company.login');
            }

            if ($request->is('center') || $request->is('center/*')) {
                return route('center.login');
            }

            return route('admin.login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard');
            }

            if ($request->is('company') || $request->is('company/*')) {
                return route('company.dashboard');
            }

            if ($request->is('center') || $request->is('center/*')) {
                return route('center.dashboard');
            }

            return route('admin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
