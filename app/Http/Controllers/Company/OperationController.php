<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Operation;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Tenant::companyId();

        $operations = Operation::query()
            ->with(['service', 'vehicle', 'center', 'user'])
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('company.operations.index', compact('operations'));
    }
}
