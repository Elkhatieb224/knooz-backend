<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Tenant::companyId();

        $vehicles = Vehicle::query()
            ->forCompany($companyId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('plate_number', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('company.vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        abort_unless($vehicle->company_id === Tenant::companyId(), 404);

        $vehicle->load(['operations' => fn ($q) => $q->with(['service', 'center'])->latest()->limit(20)]);

        return view('company.vehicles.show', compact('vehicle'));
    }
}
