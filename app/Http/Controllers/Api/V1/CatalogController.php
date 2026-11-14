<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CenterStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Center;
use App\Models\Offer;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function services(): JsonResponse
    {
        $services = Cache::remember('api:v1:services', 60, function () {
            return Service::query()
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en', 'slug', 'icon', 'description', 'base_price']);
        });

        return ApiResponse::success($services);
    }

    public function centers(Request $request): JsonResponse
    {
        $query = Center::query()
            ->where('status', CenterStatus::Active)
            ->with(['services' => fn ($q) => $q->where('is_active', true)])
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            });

        $centers = $query->paginate(min(50, max(1, (int) $request->integer('per_page', 20))));

        return ApiResponse::success($centers->items(), 'OK', 200, [
            'current_page' => $centers->currentPage(),
            'last_page' => $centers->lastPage(),
            'total' => $centers->total(),
        ]);
    }

    public function showCenter(Center $center): JsonResponse
    {
        abort_unless($center->status === CenterStatus::Active, 404);

        $center->load(['services' => fn ($q) => $q->where('is_active', true)]);

        return ApiResponse::success($center);
    }

    public function offers(): JsonResponse
    {
        $offers = Offer::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get();

        return ApiResponse::success($offers);
    }

    public function packages(Request $request): JsonResponse
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('price')
            ->get();

        return ApiResponse::success($packages);
    }
}
