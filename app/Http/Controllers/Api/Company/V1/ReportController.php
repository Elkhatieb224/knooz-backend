<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Enums\OperationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Operation;
use App\Models\Vehicle;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $companyId = Tenant::companyId();
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $base = fn () => Operation::query()
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from, $to]);

        $byStatus = [];
        $counts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        foreach (OperationStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        $spend = (float) $base()
            ->where('status', '!=', OperationStatus::Cancelled->value)
            ->sum('amount');

        return ApiResponse::success([
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'operations_total' => array_sum($byStatus),
            'operations_by_status' => $byStatus,
            'total_spend' => $spend,
            'vehicles_count' => Vehicle::query()->forCompany($companyId)->count(),
        ]);
    }
}
