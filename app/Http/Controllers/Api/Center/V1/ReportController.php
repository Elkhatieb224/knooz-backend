<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Services\Center\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        /** @var \App\Models\CenterUser $user */
        $user = $request->user();

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return ApiResponse::success(
            $this->reports->summary(
                $user->center,
                $user,
                $data['from'] ?? null,
                $data['to'] ?? null,
            )
        );
    }
}
