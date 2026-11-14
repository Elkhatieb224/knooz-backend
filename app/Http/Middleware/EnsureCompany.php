<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('company');

        $user = $request->user('company');

        if (! $user || ! $user->company_id) {
            abort(403, 'غير مصرح بالوصول إلى لوحة الشركة.');
        }

        if ($user->status !== UserStatus::Active) {
            Auth::guard('company')->logout();

            return redirect()
                ->route('company.login')
                ->withErrors(['login' => 'حسابك غير نشط. تواصل مع الإدارة.']);
        }

        $company = $user->company;

        if (! $company || $company->status !== CompanyStatus::Active) {
            Auth::guard('company')->logout();

            return redirect()
                ->route('company.login')
                ->withErrors(['login' => 'الشركة غير نشطة حالياً. تواصل مع الإدارة.']);
        }

        return $next($request);
    }
}
