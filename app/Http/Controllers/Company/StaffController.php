<?php

namespace App\Http\Controllers\Company;

use App\Enums\CompanyUserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreStaffRequest;
use App\Http\Requests\Company\UpdateStaffRequest;
use App\Models\CompanyUser;
use App\Services\Company\CompanyStaffService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(protected CompanyStaffService $staffService)
    {
    }

    public function index(Request $request): View
    {
        $staff = CompanyUser::query()
            ->where('company_id', Tenant::companyId())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('company.staff.index', [
            'staff' => $staff,
            'roles' => CompanyUserRole::labels(),
            'statuses' => UserStatus::labels(),
        ]);
    }

    public function create(): View
    {
        return view('company.staff.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $this->staffService->create(
            Tenant::companyId(),
            $request->validated(),
            $request->user('company'),
        );

        return redirect()
            ->route('company.staff.index')
            ->with('success', 'تمت إضافة الموظف بنجاح.');
    }

    public function edit(CompanyUser $staff): View
    {
        $this->ensureSameCompany($staff);

        return view('company.staff.edit', [
            'staff' => $staff,
            'roles' => $this->assignableRoles($staff),
            'isSelf' => $staff->is(auth('company')->user()),
        ]);
    }

    public function update(UpdateStaffRequest $request, CompanyUser $staff): RedirectResponse
    {
        $this->ensureSameCompany($staff);

        $this->staffService->update($staff, $request->validated(), $request->user('company'));

        return redirect()
            ->route('company.staff.index')
            ->with('success', 'تم تحديث بيانات الموظف.');
    }

    public function toggleStatus(Request $request, CompanyUser $staff): RedirectResponse
    {
        $this->ensureSameCompany($staff);

        $updated = $this->staffService->toggleStatus($staff, $request->user('company'));

        return back()->with('success', $updated->status === UserStatus::Active
            ? 'تم تفعيل الموظف.'
            : 'تم تعطيل الموظف.');
    }

    protected function ensureSameCompany(CompanyUser $staff): void
    {
        abort_unless($staff->company_id === Tenant::companyId(), 404);
    }

    /**
     * Non-owners cannot grant the owner role (unless the edited user is already an owner).
     *
     * @return array<string, string>
     */
    protected function assignableRoles(?CompanyUser $staff = null): array
    {
        $roles = CompanyUserRole::labels();

        if (! auth('company')->user()?->isOwner() && ! $staff?->isOwner()) {
            unset($roles[CompanyUserRole::Owner->value]);
        }

        return $roles;
    }
}
