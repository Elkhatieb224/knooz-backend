<?php

namespace App\Http\Controllers\Center;

use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\CenterUser;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureManager();

        $staff = CenterUser::query()
            ->where('center_id', Tenant::centerId())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('center.staff.index', [
            'staff' => $staff,
            'roles' => CenterUserRole::labels(),
            'statuses' => UserStatus::labels(),
        ]);
    }

    public function create(): View
    {
        $this->ensureManager();

        return view('center.staff.create', [
            'roles' => CenterUserRole::labels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:center_users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::enum(CenterUserRole::class)],
        ]);

        CenterUser::query()->create([
            ...$data,
            'center_id' => Tenant::centerId(),
            'status' => UserStatus::Active,
            'theme' => Theme::Light,
        ]);

        return redirect()
            ->route('center.staff.index')
            ->with('success', 'تمت إضافة موظف المركز.');
    }

    public function edit(CenterUser $staff): View
    {
        $this->ensureManager();
        $this->ensureSameCenter($staff);

        return view('center.staff.edit', [
            'staff' => $staff,
            'roles' => CenterUserRole::labels(),
            'isSelf' => $staff->is(auth('center')->user()),
        ]);
    }

    public function update(Request $request, CenterUser $staff): RedirectResponse
    {
        $this->ensureManager();
        $this->ensureSameCenter($staff);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('center_users', 'email')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', Rule::enum(CenterUserRole::class)],
        ]);

        if ($staff->is(auth('center')->user())) {
            $data['role'] = $staff->role?->value ?? CenterUserRole::Manager->value;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $staff->update($data);

        return redirect()
            ->route('center.staff.index')
            ->with('success', 'تم تحديث الموظف.');
    }

    public function toggleStatus(CenterUser $staff): RedirectResponse
    {
        $this->ensureManager();
        $this->ensureSameCenter($staff);

        if ($staff->is(auth('center')->user())) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الحالي.');
        }

        $staff->update([
            'status' => $staff->status === UserStatus::Active
                ? UserStatus::Inactive
                : UserStatus::Active,
        ]);

        return back()->with('success', $staff->status === UserStatus::Active
            ? 'تم تفعيل الموظف.'
            : 'تم تعطيل الموظف.');
    }

    protected function ensureManager(): void
    {
        $user = auth('center')->user();
        abort_unless($user && $user->role === CenterUserRole::Manager, 403);
    }

    protected function ensureSameCenter(CenterUser $staff): void
    {
        abort_unless((int) $staff->center_id === (int) Tenant::centerId(), 404);
    }
}
