<?php

namespace App\Http\Controllers\Company;

use App\Enums\Theme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('company.profile.edit', [
            'user' => auth('company')->user(),
            'themes' => Theme::labels(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user('company');
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'theme' => $user->theme->value]);
        }

        return back()->with('success', 'تم تحديث الملف الشخصي.');
    }

    public function updateTheme(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', Rule::enum(Theme::class)],
        ]);

        $theme = $data['theme'] instanceof Theme ? $data['theme']->value : $data['theme'];
        $request->user('company')->update(['theme' => $theme]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'theme' => $theme]);
        }

        return back()->with('success', 'تم تحديث الثيم.');
    }
}
