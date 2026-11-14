<?php

namespace App\Http\Controllers\Center\Auth;

use App\Enums\CenterStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PanelLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::guard('center')->check()) {
            return redirect()->route('center.dashboard');
        }

        return view('center.auth.login');
    }

    public function store(PanelLoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');
        $remember = $request->boolean('remember');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::guard('center')->attempt([$field => $login, 'password' => $password], $remember)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'بيانات الدخول غير صحيحة.']);
        }

        $user = Auth::guard('center')->user();

        if ($user->status !== UserStatus::Active || ! $user->center_id) {
            Auth::guard('center')->logout();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => $user->center_id
                    ? 'حسابك غير نشط. تواصل مع الإدارة.'
                    : 'حساب المركز غير مرتبط بمركز خدمة.']);
        }

        $center = $user->center;
        if (! $center || $center->status !== CenterStatus::Active) {
            Auth::guard('center')->logout();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'المركز غير نشط حالياً. تواصل مع الإدارة.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('center.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('center')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('center.login');
    }
}
