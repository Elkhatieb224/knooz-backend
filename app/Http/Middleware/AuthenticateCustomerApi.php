<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\User;
use App\Support\CustomerRoles;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            $plain = $request->bearerToken();
            if (! $plain) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $accessToken = PersonalAccessToken::findToken($plain);
            if (! $accessToken || ! $accessToken->tokenable instanceof User) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $user = $accessToken->tokenable;
            $accessToken->forceFill(['last_used_at' => now()])->save();
            $request->setUserResolver(fn () => $user);
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
