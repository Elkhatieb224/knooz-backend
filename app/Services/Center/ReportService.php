<?php

namespace App\Services\Center;

use App\Enums\BookingStatus;
use App\Enums\OperationStatus;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Operation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Center $center, CenterUser $actor, ?string $from = null, ?string $to = null): array
    {
        $fromAt = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toAt = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $operationsQuery = Operation::query()
            ->forCenter($center->id)
            ->whereBetween('created_at', [$fromAt, $toAt]);

        $completedQuery = Operation::query()
            ->forCenter($center->id)
            ->where('status', OperationStatus::Completed)
            ->whereBetween(DB::raw('COALESCE(completed_at, created_at)'), [$fromAt, $toAt]);

        $operationsByStatus = Operation::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->forCenter($center->id)
            ->whereBetween('created_at', [$fromAt, $toAt])
            ->groupBy('status')
            ->pluck('total', 'status');

        $bookingsByStatus = Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->forCenter($center->id)
            ->whereBetween('created_at', [$fromAt, $toAt])
            ->groupBy('status')
            ->pluck('total', 'status');

        $payload = [
            'from' => $fromAt->toIso8601String(),
            'to' => $toAt->toIso8601String(),
            'operations_total' => (clone $operationsQuery)->count(),
            'operations_completed' => (clone $completedQuery)->count(),
            'operations_in_progress' => Operation::query()
                ->forCenter($center->id)
                ->where('status', OperationStatus::InProgress)
                ->count(),
            'bookings_total' => Booking::query()
                ->forCenter($center->id)
                ->whereBetween('created_at', [$fromAt, $toAt])
                ->count(),
            'bookings_cancelled' => Booking::query()
                ->forCenter($center->id)
                ->where('status', BookingStatus::Cancelled)
                ->whereBetween('created_at', [$fromAt, $toAt])
                ->count(),
            'operations_by_status' => $operationsByStatus,
            'bookings_by_status' => $bookingsByStatus,
            'can_view_revenue' => false,
            'revenue_net' => null,
            'revenue_gross' => null,
        ];

        if ($this->shouldExposeRevenue($center, $actor)) {
            $gross = (float) (clone $completedQuery)->sum('amount');
            $net = $this->netRevenue($center->id, $fromAt, $toAt);

            $payload['can_view_revenue'] = true;
            $payload['revenue_gross'] = round($gross, 2);
            $payload['revenue_net'] = round($net, 2);
        }

        return $payload;
    }

    public function shouldExposeRevenue(Center $center, CenterUser $actor): bool
    {
        return $actor->canViewRevenue() && $center->showsRevenue();
    }

    protected function netRevenue(int $centerId, Carbon $fromAt, Carbon $toAt): float
    {
        $rows = Operation::query()
            ->from('operations')
            ->leftJoin('center_service', function ($join) use ($centerId) {
                $join->on('center_service.service_id', '=', 'operations.service_id')
                    ->where('center_service.center_id', '=', $centerId);
            })
            ->where('operations.center_id', $centerId)
            ->where('operations.status', OperationStatus::Completed->value)
            ->whereBetween(DB::raw('COALESCE(operations.completed_at, operations.created_at)'), [$fromAt, $toAt])
            ->get([
                'operations.amount',
                'center_service.app_profit',
                'center_service.contract_price',
            ]);

        $net = 0.0;
        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            if ($row->app_profit !== null) {
                $net += max(0, $amount - (float) $row->app_profit);
            } elseif ($row->contract_price !== null) {
                $net += (float) $row->contract_price;
            } else {
                $net += $amount;
            }
        }

        return $net;
    }
}
