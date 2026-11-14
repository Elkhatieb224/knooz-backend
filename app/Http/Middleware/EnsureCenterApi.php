<?php

namespace App\Http\Middleware;

use App\Enums\CenterStatus;
use App\Enums\UserStatus;
use App\Models\CenterUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenterApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = (string) $request->attributes->get('panel', '');
        $user = $request->user();

        if ($panel !== 'center' || ! $user instanceof CenterUser) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المسار مخصص لتطبيق المراكز فقط.',
            ], 403);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'حسابك غير نشط. تواصل مع الإدارة.',
            ], 403);
        }

        if (! $user->center_id) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المركز غير مرتبط بمركز خدمة.',
            ], 403);
        }

        $center = $user->center;

        if (! $center || $center->status !== CenterStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'المركز غير نشط حالياً.',
            ], 403);
        }

        Auth::shouldUse('center');
        Auth::guard('center')->setUser($user);

        return $next($request);
    }
}
