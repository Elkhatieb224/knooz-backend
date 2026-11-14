<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Company\OperationResource;
use App\Http\Resources\Company\SubscriptionResource;
use App\Services\Company\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\CompanyUser $user */
        $user = $request->user();
        $company = $user->company()->with('wallet')->firstOrFail();
        $stats = $this->dashboard->stats($company);

        return ApiResponse::success([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'status' => $company->status?->value,
                'payment_mode' => $company->payment_mode?->value,
                'wallet_balance' => (float) ($company->wallet?->balance ?? 0),
            ],
            'stats' => [
                'vehicles' => $stats['vehicles'],
                'active_vehicles' => $stats['active_vehicles'],
                'operations' => $stats['operations'],
                'wallet_balance' => $stats['wallet_balance'],
                'monthly_spend' => $stats['monthly_spend'],
                'subscription' => $stats['subscription']
                    ? (new SubscriptionResource($stats['subscription']))->resolve()
                    : null,
            ],
            'recent_operations' => OperationResource::collection(
                $this->dashboard->recentOperations($company)
            )->resolve(),
        ]);
    }
}
