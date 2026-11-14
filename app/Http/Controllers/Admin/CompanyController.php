<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Vehicle;
use App\Services\Notifications\NotificationHooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('companies.view');

        $companies = Company::query()
            ->withCount('vehicles')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('commercial_register', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        $this->authorize('companies.manage');

        return view('admin.companies.create', [
            'statuses' => CompanyStatus::labels(),
            'paymentModes' => PaymentMode::labels(),
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $this->authorize('companies.manage');

        $company = Company::query()->create($request->validated());
        $this->notificationHooks->companyCreated($company);

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'تم إنشاء الشركة بنجاح.');
    }

    public function show(Company $company): View
    {
        $this->authorize('companies.view');

        $company->load([
            'vehicles',
            'invoices',
            'subscriptions.package',
            'wallet',
            'members',
            'companyUsers' => fn ($q) => $q->orderBy('name'),
        ]);

        $availableVehicles = Vehicle::query()
            ->whereNull('company_id')
            ->orderBy('plate_number')
            ->limit(100)
            ->get(['id', 'plate_number', 'brand', 'model']);

        return view('admin.companies.show', compact('company', 'availableVehicles'));
    }

    public function edit(Company $company): View
    {
        $this->authorize('companies.manage');

        return view('admin.companies.edit', [
            'company' => $company,
            'statuses' => CompanyStatus::labels(),
            'paymentModes' => PaymentMode::labels(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('companies.manage');

        $company->update($request->validated());

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'تم تحديث الشركة بنجاح.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('companies.manage');

        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'تم حذف الشركة بنجاح.');
    }

    public function attachVehicle(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('companies.manage');

        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        Vehicle::query()->whereKey($data['vehicle_id'])->update([
            'company_id' => $company->id,
            'user_id' => null,
        ]);

        return back()->with('success', 'تم ربط المركبة بالشركة.');
    }
}
