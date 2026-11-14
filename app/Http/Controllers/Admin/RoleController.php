<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('roles.manage');

        $admins = AdminUser::query()
            ->whereIn('role', Roles::adminRoles())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.roles.index', [
            'admins' => $admins,
            'matrix' => Permissions::MATRIX,
            'labels' => Permissions::labels(),
            'roleLabels' => Roles::adminLabels(),
        ]);
    }

    public function edit(AdminUser $admin): View
    {
        $this->authorize('roles.manage');
        abort_unless(Roles::isAdmin($admin->role), 404);

        return view('admin.roles.edit', [
            'adminUser' => $admin,
            'roles' => Roles::adminLabels(),
            'statuses' => UserStatus::labels(),
        ]);
    }

    public function update(Request $request, AdminUser $admin): RedirectResponse
    {
        $this->authorize('roles.manage');
        abort_unless(Roles::isAdmin($admin->role), 404);

        $data = $request->validate([
            'role' => ['required', Rule::in(Roles::adminRoles())],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        $current = $request->user('admin');

        if ($admin->id === $current?->id && $data['role'] !== UserRole::SuperAdmin->value) {
            return back()->with('error', 'لا يمكن تغيير دور حسابك الحالي.');
        }

        $admin->update($data);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم تحديث صلاحيات المستخدم.');
    }
}
