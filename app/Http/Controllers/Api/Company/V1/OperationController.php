<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\OperationResource;
use App\Models\Operation;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationController extends Controller
{
    use PaginationMeta;

    public function index(Request $request): JsonResponse
    {
        $companyId = Tenant::companyId();

        $operations = Operation::query()
            ->with(['service', 'vehicle', 'center'])
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
            })
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            OperationResource::collection($operations)->resolve(),
            'OK',
            200,
            $this->meta($operations)
        );
    }
}
