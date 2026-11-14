<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = (string) $request->attributes->get('panel', '');
        $user = $request->user();

        if ($panel !== 'company' || ! $user instanceof CompanyUser) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المسار مخصص لتطبيق الشركات فقط.',
            ], 403);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'حسابك غير نشط. تواصل مع الإدارة.',
            ], 403);
        }

        if (! $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'حساب الشركة غير مرتبط بشركة.',
            ], 403);
        }

        $company = $user->company;

        if (! $company || $company->status !== CompanyStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'الشركة غير نشطة حالياً.',
            ], 403);
        }

        Auth::shouldUse('company');
        Auth::guard('company')->setUser($user);

        return $next($request);
    }
}
