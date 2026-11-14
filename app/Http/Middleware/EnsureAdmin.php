<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');

        $user = $request->user('admin');

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if ($user->status !== UserStatus::Active) {
            Auth::guard('admin')->logout();

            return redirect()
                ->route('admin.login')
                ->withErrors(['login' => 'حسابك غير نشط. تواصل مع الإدارة.']);
        }

        return $next($request);
    }
}
