<?php

namespace App\Http\Middleware;

use App\Enums\CenterStatus;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenter
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('center');

        $user = $request->user('center');

        if (! $user || ! $user->center_id) {
            abort(403, 'غير مصرح بالوصول إلى لوحة المركز.');
        }

        if ($user->status !== UserStatus::Active) {
            Auth::guard('center')->logout();

            return redirect()
                ->route('center.login')
                ->withErrors(['login' => 'حسابك غير نشط. تواصل مع الإدارة.']);
        }

        $center = $user->center;

        if (! $center || $center->status !== CenterStatus::Active) {
            Auth::guard('center')->logout();

            return redirect()
                ->route('center.login')
                ->withErrors(['login' => 'المركز غير نشط حالياً. تواصل مع الإدارة.']);
        }

        return $next($request);
    }
}
