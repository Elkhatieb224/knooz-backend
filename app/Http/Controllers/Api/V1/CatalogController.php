<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CenterStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Center;
use App\Models\Offer;
use App\Models\Package;
use App\Models\Service;
use App\Support\ServiceChannels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function services(Request $request): JsonResponse
    {
        $channel = $request->string('channel')->toString();
        $cacheKey = 'api:v1:services'.($channel !== '' ? ':'.$channel : '');

        $services = Cache::remember($cacheKey, 60, function () use ($channel) {
            return Service::query()
                ->where('is_active', true)
                ->when($channel !== '', fn ($q) => $q->where('channel', $channel))
                ->orderBy('sort_order')
                ->orderBy('name_ar')
                ->get([
                    'id', 'name_ar', 'name_en', 'slug', 'icon', 'description', 'base_price',
                    'channel', 'kind_key', 'requires', 'flow_hint', 'sort_order',
                ]);
        });

        return ApiResponse::success($services);
    }

    public function centers(Request $request): JsonResponse
    {
        $channel = $request->string('channel')->toString();
        $serviceId = $request->filled('service_id') ? (int) $request->integer('service_id') : null;

        $query = Center::query()
            ->where('status', CenterStatus::Active)
            ->with(['services' => function ($q) use ($channel) {
                $q->where('is_active', true)
                    ->wherePivot('is_available', true)
                    ->when($channel !== '', fn ($inner) => $inner->where('services.channel', $channel));
            }])
            ->when(
                in_array($channel, [ServiceChannels::CENTER, ServiceChannels::HOME, ServiceChannels::EMERGENCY], true),
                fn ($q) => $q->offeringChannel($channel)
            )
            ->when($serviceId, function ($q) use ($serviceId) {
                $q->whereHas('services', function ($inner) use ($serviceId) {
                    $inner->where('services.id', $serviceId)
                        ->where('services.is_active', true)
                        ->where('center_service.is_available', true);
                });
            })
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

        $items = collect($centers->items())->map(fn (Center $center) => $this->publicCenterPayload($center));

        return ApiResponse::success($items, 'OK', 200, [
            'current_page' => $centers->currentPage(),
            'last_page' => $centers->lastPage(),
            'total' => $centers->total(),
        ]);
    }

    public function showCenter(Center $center): JsonResponse
    {
        abort_unless($center->status === CenterStatus::Active, 404);

        $center->load(['services' => fn ($q) => $q->where('is_active', true)->wherePivot('is_available', true)]);

        return ApiResponse::success($this->publicCenterPayload($center));
    }

    /**
     * Hide internal pricing pivot fields from the public catalog.
     *
     * @return array<string, mixed>
     */
    protected function publicCenterPayload(Center $center): array
    {
        return [
            'id' => $center->id,
            'name' => $center->name,
            'type' => $center->type?->value ?? $center->type,
            'phone' => $center->phone,
            'email' => $center->email,
            'address' => $center->address,
            'city' => $center->city,
            'lat' => $center->lat,
            'lng' => $center->lng,
            'status' => $center->status?->value ?? $center->status,
            'channels' => [
                'center' => (bool) $center->offers_center_service,
                'home' => (bool) $center->offers_home_service,
                'emergency' => (bool) $center->offers_emergency_service,
            ],
            'offers_center_service' => (bool) $center->offers_center_service,
            'offers_home_service' => (bool) $center->offers_home_service,
            'offers_emergency_service' => (bool) $center->offers_emergency_service,
            'services' => $center->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name_ar' => $service->name_ar,
                'name_en' => $service->name_en,
                'slug' => $service->slug,
                'icon' => $service->icon,
                'description' => $service->description,
                'base_price' => $service->base_price,
                'channel' => $service->channel,
                'kind_key' => $service->kind_key,
                'requires' => $service->requires,
                'flow_hint' => $service->flow_hint,
                'is_available' => (bool) ($service->pivot?->is_available ?? true),
            ])->values(),
        ];
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
