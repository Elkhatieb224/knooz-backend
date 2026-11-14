<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Tenant::companyId();

        $invoices = Invoice::query()
            ->forCompany($companyId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('number', 'like', $term);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('company.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        abort_unless($invoice->company_id === Tenant::companyId(), 404);

        $invoice->load(['items.vehicle', 'items.service']);

        return view('company.invoices.show', compact('invoice'));
    }
}
