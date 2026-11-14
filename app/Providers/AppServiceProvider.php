<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Carbon::setLocale(config('app.locale', 'ar'));
        app()->setLocale(config('app.locale', 'ar'));

        Route::bind('admin', function (string $value) {
            return AdminUser::query()->whereKey($value)->firstOrFail();
        });

        foreach (array_keys(Permissions::MATRIX) as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return Permissions::allows($user, $permission);
            });
        }

        Blade::if('canperm', function (string $permission) {
            return auth('admin')->check() && auth('admin')->user()->can($permission);
        });
    }
}
