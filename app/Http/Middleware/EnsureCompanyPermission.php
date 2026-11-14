<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: company.can:staff.manage  (any of several: company.can:a,b)
 */
class EnsureCompanyPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::guard('company')->user() ?? $request->user();

        $allowed = $user instanceof CompanyUser
            && collect($permissions)->contains(fn (string $permission) => $user->can($permission));

        if (! $allowed) {
            $message = 'ليس لديك صلاحية للوصول إلى هذه الصفحة.';

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
