<?php

namespace App\Services\Center;

use App\Enums\BookingStatus;
use App\Enums\OperationStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Operation;
use App\Models\User;

class DashboardService
{
    public function __construct(protected ReportService $reports)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(int $centerId): array
    {
        $today = now()->toDateString();

        return [
            'bookings_today' => Booking::query()
                ->where('center_id', $centerId)
                ->whereDate('scheduled_at', $today)
                ->count(),
            'operations_in_progress' => Operation::query()
                ->where('center_id', $centerId)
                ->where('status', OperationStatus::InProgress)
                ->count(),
            'operations_completed_today' => Operation::query()
                ->where('center_id', $centerId)
                ->where('status', OperationStatus::Completed)
                ->whereDate('completed_at', $today)
                ->count(),
            'staff_count' => User::query()
                ->where('center_id', $centerId)
                ->whereIn('role', [
                    UserRole::BranchEmployee->value,
                    UserRole::EmergencyEmployee->value,
                    UserRole::CenterManager->value,
                ])
                ->count(),
            'bookings_new' => Booking::query()
                ->where('center_id', $centerId)
                ->where('status', BookingStatus::New)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forApi(Center $center, CenterUser $actor): array
    {
        $today = now()->toDateString();
        $base = $this->stats($center->id);

        $todayRevenueNet = null;
        $canViewRevenue = $this->reports->shouldExposeRevenue($center, $actor);

        if ($canViewRevenue) {
            $completedToday = Operation::query()
                ->where('center_id', $center->id)
                ->where('status', OperationStatus::Completed)
                ->whereDate('completed_at', $today)
                ->with('service')
                ->get();

            $pivots = $center->services()->get()->keyBy('id');
            $net = 0.0;
            foreach ($completedToday as $op) {
                $pivot = $pivots->get($op->service_id)?->pivot;
                $amount = (float) $op->amount;
                if ($pivot && $pivot->app_profit !== null) {
                    $net += max(0, $amount - (float) $pivot->app_profit);
                } elseif ($pivot && $pivot->contract_price !== null) {
                    $net += (float) $pivot->contract_price;
                } else {
                    $net += $amount;
                }
            }
            $todayRevenueNet = round($net, 2);
        }

        $incomingBookings = Booking::query()
            ->with(['user', 'vehicle', 'service'])
            ->forCenter($center->id)
            ->whereIn('status', [BookingStatus::New, BookingStatus::InProgress])
            ->latest('scheduled_at')
            ->limit(10)
            ->get();

        $activeOperations = Operation::query()
            ->with(['user', 'vehicle', 'service'])
            ->forCenter($center->id)
            ->where('status', OperationStatus::InProgress)
            ->latest('started_at')
            ->limit(10)
            ->get();

        return [
            'stats' => array_merge($base, [
                'can_view_revenue' => $canViewRevenue,
                'revenue_today_net' => $todayRevenueNet,
            ]),
            'incoming_bookings' => $incomingBookings,
            'active_operations' => $activeOperations,
            'quick_actions' => [
                ['key' => 'lookup', 'label' => 'مسح / بحث مركبة'],
                ['key' => 'create_operation', 'label' => 'عملية جديدة'],
                ['key' => 'bookings', 'label' => 'الحجوزات'],
                ['key' => 'reports', 'label' => 'التقارير'],
            ],
        ];
    }
}
