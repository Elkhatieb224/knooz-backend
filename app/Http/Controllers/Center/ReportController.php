<?php

namespace App\Http\Controllers\Center;

use App\Enums\BookingStatus;
use App\Enums\OperationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Operation;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $centerId = Tenant::centerId();
        $monthStart = now()->startOfMonth();

        $operationsByStatus = Operation::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->forCenter($centerId)
            ->groupBy('status')
            ->pluck('total', 'status');

        $bookingsByStatus = Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->forCenter($centerId)
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthOperations = Operation::query()
            ->forCenter($centerId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $monthCompleted = Operation::query()
            ->forCenter($centerId)
            ->where('status', OperationStatus::Completed)
            ->where('completed_at', '>=', $monthStart)
            ->count();

        $monthRevenue = (float) Operation::query()
            ->forCenter($centerId)
            ->where('status', OperationStatus::Completed)
            ->where('completed_at', '>=', $monthStart)
            ->sum('amount');

        $monthBookings = Booking::query()
            ->forCenter($centerId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $cancelledBookings = Booking::query()
            ->forCenter($centerId)
            ->where('status', BookingStatus::Cancelled)
            ->where('created_at', '>=', $monthStart)
            ->count();

        return view('center.reports.index', [
            'operationsByStatus' => $operationsByStatus,
            'bookingsByStatus' => $bookingsByStatus,
            'operationStatuses' => OperationStatus::labels(),
            'bookingStatuses' => BookingStatus::labels(),
            'monthOperations' => $monthOperations,
            'monthCompleted' => $monthCompleted,
            'monthRevenue' => $monthRevenue,
            'monthBookings' => $monthBookings,
            'cancelledBookings' => $cancelledBookings,
        ]);
    }
}
