<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\VehicleResource;
use App\Models\Vehicle;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    use PaginationMeta;

    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->forCompany(Tenant::companyId())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('plate_number', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            VehicleResource::collection($vehicles)->resolve(),
            'OK',
            200,
            $this->meta($vehicles)
        );
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->forCompany(Tenant::companyId())
            ->whereKey($id)
            ->firstOrFail();

        $vehicle->load(['operations' => fn ($q) => $q->with(['service', 'center'])->latest()->limit(20)]);

        return ApiResponse::success((new VehicleResource($vehicle))->resolve());
    }
}
