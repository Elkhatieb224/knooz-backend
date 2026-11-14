<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\SubscriptionResource;
use App\Models\Subscription;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use PaginationMeta;

    public function index(Request $request): JsonResponse
    {
        $companyId = Tenant::companyId();

        $active = Subscription::query()
            ->with('package')
            ->forCompany($companyId)
            ->where('status', SubscriptionStatus::Active)
            ->latest('starts_at')
            ->first();

        $history = Subscription::query()
            ->with('package')
            ->forCompany($companyId)
            ->latest('starts_at')
            ->paginate($this->perPage($request, 10));

        return ApiResponse::success([
            'active' => $active ? (new SubscriptionResource($active))->resolve() : null,
            'history' => SubscriptionResource::collection($history)->resolve(),
        ], 'OK', 200, $this->meta($history));
    }
}
