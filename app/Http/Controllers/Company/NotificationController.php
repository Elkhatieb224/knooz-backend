<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\HandlesPanelNotifications;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use HandlesPanelNotifications;

    protected function notificationPanel(): string
    {
        return 'company';
    }

    protected function notificationUser(Request $request): Authenticatable&Model
    {
        /** @var Authenticatable&Model $user */
        $user = $request->user('company');

        return $user;
    }

    protected function notificationsIndexView(): string
    {
        return 'notifications.index';
    }
}
