<?php

namespace App\Http\Controllers\Admin\Auth;

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
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(PanelLoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');
        $remember = $request->boolean('remember');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::guard('admin')->attempt([$field => $login, 'password' => $password], $remember)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'بيانات الدخول غير صحيحة.']);
        }

        $user = Auth::guard('admin')->user();

        if ($user->status !== UserStatus::Active) {
            Auth::guard('admin')->logout();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'حسابك غير نشط. تواصل مع الإدارة.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
