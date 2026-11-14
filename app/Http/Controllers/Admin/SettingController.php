<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PackageType;
use App\Enums\Theme;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('settings.manage');

        $keys = [
            'app_name', 'app_name_en', 'support_phone', 'support_email',
            'currency', 'default_theme', 'vat_percent', 'emergency_radius_km',
        ];

        $settings = collect($keys)->mapWithKeys(
            fn ($key) => [$key => Setting::getValue($key, '')]
        );

        return view('admin.settings.index', [
            'settings' => $settings,
            'services' => Service::query()->orderBy('name_ar')->get(),
            'packages' => Package::query()->latest()->paginate(10, ['*'], 'packages_page'),
            'packageTypes' => PackageType::labels(),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_name_en' => ['nullable', 'string', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'default_theme' => ['required', Rule::in(['light', 'dark'])],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'emergency_radius_km' => ['nullable', 'numeric', 'min:1'],
        ]);

        foreach ($data as $key => $value) {
            Setting::setValue($key, $value);
        }

        return back()->with('success', 'تم حفظ الإعدادات العامة.');
    }

    public function toggleService(Service $service): RedirectResponse
    {
        $this->authorize('settings.manage');

        $service->update(['is_active' => ! $service->is_active]);

        return back()->with('success', 'تم تحديث حالة الخدمة.');
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PackageType::class)],
            'duration_days' => ['required', 'integer', 'min:1'],
            'operations_count' => ['required', 'integer', 'min:0'],
            'vehicles_count' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['free_services'] = $request->input('free_services', []);

        Package::query()->create($data);

        return back()->with('success', 'تم إنشاء الباقة.');
    }

    public function updatePackage(Request $request, Package $package): RedirectResponse
    {
        $this->authorize('settings.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PackageType::class)],
            'duration_days' => ['required', 'integer', 'min:1'],
            'operations_count' => ['required', 'integer', 'min:0'],
            'vehicles_count' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['free_services'] = $request->input('free_services', []);

        $package->update($data);

        return back()->with('success', 'تم تحديث الباقة.');
    }

    public function destroyPackage(Package $package): RedirectResponse
    {
        $this->authorize('settings.manage');

        $package->delete();

        return back()->with('success', 'تم حذف الباقة.');
    }

    public function profile(): View
    {
        return view('admin.settings.profile', [
            'user' => auth('admin')->user(),
            'themes' => Theme::labels(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user('admin');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('admin_users', 'phone')->ignore($user->id)],
            'theme' => ['required', Rule::enum(Theme::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

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
        $request->user('admin')->update(['theme' => $theme]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'theme' => $theme]);
        }

        return back()->with('success', 'تم تحديث الثيم.');
    }
}

