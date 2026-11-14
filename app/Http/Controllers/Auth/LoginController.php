<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        foreach (['admin', 'company', 'center', 'web'] as $guard) {
            if (auth($guard)->check()) {
                auth($guard)->logout();
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
