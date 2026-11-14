<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Support\CustomerRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if (! CustomerRoles::isCustomer($user)) {
            return ApiResponse::error('غير مصرح لهذا الحساب باستخدام تطبيق العملاء.', 403);
        }

        if ($user->status !== UserStatus::Active) {
            return ApiResponse::error('الحساب غير نشط.', 403);
        }

        return $next($request);
    }
}
