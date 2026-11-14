<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\InvoiceResource;
use App\Models\Invoice;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use PaginationMeta;

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->forCompany(Tenant::companyId())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('number', 'like', '%'.$request->string('q').'%');
            })
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            InvoiceResource::collection($invoices)->resolve(),
            'OK',
            200,
            $this->meta($invoices)
        );
    }

    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::query()
            ->forCompany(Tenant::companyId())
            ->with(['items.vehicle', 'items.service'])
            ->whereKey($id)
            ->firstOrFail();

        return ApiResponse::success((new InvoiceResource($invoice))->resolve());
    }
}
