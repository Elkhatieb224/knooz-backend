<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Requests\Admin\UpdateVehicleRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Shared\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(protected QrCodeService $qrCodeService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('vehicles.view');

        $vehicles = Vehicle::query()
            ->with(['user', 'company'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('plate_number', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('qr_code', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        $this->authorize('vehicles.manage');

        return view('admin.vehicles.create', $this->formData());
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->authorize('vehicles.manage');

        $data = $request->validated();
        $codes = $this->qrCodeService->generatePair('VEH');

        Vehicle::query()->create([
            ...$data,
            'qr_code' => $codes['qr_code'],
            'nfc_code' => $codes['nfc_code'],
        ]);

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'تم إنشاء المركبة بنجاح.');
    }

    public function show(Vehicle $vehicle): View
    {
        $this->authorize('vehicles.view');

        $vehicle->load(['user', 'company', 'operations.service', 'bookings']);

        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('vehicles.manage');

        return view('admin.vehicles.edit', array_merge($this->formData(), compact('vehicle')));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('vehicles.manage');

        $vehicle->update($request->validated());

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'تم تحديث المركبة بنجاح.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('vehicles.manage');

        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'تم حذف المركبة بنجاح.');
    }

    public function regenerateCodes(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('vehicles.manage');

        $vehicle->update($this->qrCodeService->generatePair('VEH'));

        return back()->with('success', 'تم توليد رموز QR/NFC جديدة للمركبة.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'statuses' => VehicleStatus::labels(),
            'users' => User::query()
                ->where('role', UserRole::CustomerIndividual)
                ->orderBy('name')
                ->get(),
            'companies' => Company::query()->orderBy('name')->get(),
        ];
    }
}
