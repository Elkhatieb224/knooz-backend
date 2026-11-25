<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Vehicle;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Notifications\NotificationHooks;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        protected NotificationHooks $notificationHooks,
        protected NotificationService $notifications,
        protected AdminActivityLogger $activity,
    ) {
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

    public function approve(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('companies.manage');

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $comment = trim((string) ($data['notes'] ?? ''));
        $company->update([
            'status' => CompanyStatus::Active,
            'notes' => $this->appendReviewNote($company->notes, 'اعتماد', $comment),
        ]);

        $this->notifyCompanyUsers(
            $company,
            'تم اعتماد شركتك',
            $comment !== ''
                ? "تم تفعيل حساب شركتك في كنوز.\nملاحظة الإدارة: {$comment}"
                : 'تم تفعيل حساب شركتك في كنوز ويمكنك تسجيل الدخول الآن.',
        );

        $this->activity->log(
            $request->user('admin'),
            'companies.approve',
            "اعتماد الشركة: {$company->name}",
            $company,
            ['notes' => $comment],
            $request,
        );

        return back()->with('success', 'تم اعتماد الشركة وإشعار مستخدميها.');
    }

    public function reject(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('companies.manage');

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'notes.required' => 'يجب كتابة سبب الرفض/الإيقاف.',
        ]);

        $comment = trim($data['notes']);
        $company->update([
            'status' => CompanyStatus::Suspended,
            'notes' => $this->appendReviewNote($company->notes, 'رفض/إيقاف', $comment),
        ]);

        $this->notifyCompanyUsers(
            $company,
            'تم إيقاف/رفض الشركة',
            "تم تغيير حالة شركتك إلى موقوفة.\nالسبب: {$comment}",
        );

        $this->activity->log(
            $request->user('admin'),
            'companies.reject',
            "رفض/إيقاف الشركة: {$company->name}",
            $company,
            ['notes' => $comment],
            $request,
        );

        return back()->with('success', 'تم إيقاف الشركة وإشعار مستخدميها بالسبب.');
    }

    protected function appendReviewNote(?string $existing, string $action, string $comment): string
    {
        $line = '['.now()->format('Y-m-d H:i')."] {$action}";
        if ($comment !== '') {
            $line .= ': '.$comment;
        }

        $existing = trim((string) $existing);

        return $existing === '' ? $line : $existing."\n".$line;
    }

    protected function notifyCompanyUsers(Company $company, string $title, string $body): void
    {
        $users = $this->notifications->companyUsers((int) $company->id);
        if ($users->isEmpty()) {
            return;
        }

        $this->notifications->notify(
            $users,
            NotificationType::AdminAction,
            $title,
            $body,
            $company,
            eventKey: sprintf('company:%d:review:%s', $company->id, now()->format('YmdHis')),
            queue: false,
        );
    }
}
