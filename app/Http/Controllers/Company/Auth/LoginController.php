<?php

namespace App\Http\Controllers\Company\Auth;

use App\Enums\CompanyStatus;
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
        if (Auth::guard('company')->check()) {
            return redirect()->route('company.dashboard');
        }

        return view('company.auth.login');
    }

    public function store(PanelLoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');
        $remember = $request->boolean('remember');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::guard('company')->attempt([$field => $login, 'password' => $password], $remember)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'بيانات الدخول غير صحيحة.']);
        }

        $user = Auth::guard('company')->user();

        if ($user->status !== UserStatus::Active || ! $user->company_id) {
            Auth::guard('company')->logout();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => $user->company_id
                    ? 'حسابك غير نشط. تواصل مع الإدارة.'
                    : 'حساب الشركة غير مرتبط بشركة.']);
        }

        $company = $user->company;
        if (! $company || $company->status !== CompanyStatus::Active) {
            Auth::guard('company')->logout();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'الشركة غير نشطة حالياً. تواصل مع الإدارة.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('company.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('company')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}
