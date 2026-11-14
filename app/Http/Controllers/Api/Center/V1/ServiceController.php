<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Center\UpdateServiceAvailabilityRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $center = $user->center;

        $services = $center->services()
            ->orderBy('services.name_ar')
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'name_ar' => $service->name_ar,
                'name_en' => $service->name_en,
                'base_price' => (float) $service->base_price,
                'contract_price' => $service->pivot->contract_price !== null
                    ? (float) $service->pivot->contract_price
                    : null,
                'is_available' => (bool) $service->pivot->is_available,
                // Prices are read-only for centers; app_profit not exposed to staff.
                'app_profit' => $user->canViewRevenue() && $center->showsRevenue()
                    ? ($service->pivot->app_profit !== null ? (float) $service->pivot->app_profit : null)
                    : null,
            ]);

        return ApiResponse::success($services);
    }

    public function update(UpdateServiceAvailabilityRequest $request, int $serviceId): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $center = $user->center;

        if (! $center->services()->where('services.id', $serviceId)->exists()) {
            abort(404);
        }

        $center->services()->updateExistingPivot($serviceId, [
            'is_available' => $request->boolean('is_available'),
        ]);

        $service = $center->services()->where('services.id', $serviceId)->first();

        return ApiResponse::success([
            'id' => $service->id,
            'is_available' => (bool) $service->pivot->is_available,
            'contract_price' => $service->pivot->contract_price !== null
                ? (float) $service->pivot->contract_price
                : null,
            'name_ar' => $service->name_ar,
        ], 'تم تحديث توفر الخدمة.');
    }
}
