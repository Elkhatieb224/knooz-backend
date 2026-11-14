<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\BookingResource;
use App\Http\Resources\Center\OperationResource;
use App\Services\Center\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();
        $center = $user->center;
        $payload = $this->dashboard->forApi($center, $user);

        return ApiResponse::success([
            'stats' => $payload['stats'],
            'incoming_bookings' => BookingResource::collection($payload['incoming_bookings']),
            'active_operations' => OperationResource::collection($payload['active_operations']),
            'quick_actions' => $payload['quick_actions'],
        ]);
    }
}
