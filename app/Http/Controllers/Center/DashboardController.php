<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Operation;
use App\Services\Center\DashboardService;
use App\Support\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        $centerId = Tenant::centerId();
        $center = auth('center')->user()->center;

        return view('center.dashboard.index', [
            'center' => $center,
            'stats' => $this->dashboard->stats($centerId),
            'todayBookings' => Booking::query()
                ->with(['user', 'vehicle', 'service'])
                ->forCenter($centerId)
                ->whereDate('scheduled_at', now()->toDateString())
                ->orderBy('scheduled_at')
                ->limit(8)
                ->get(),
            'recentOperations' => Operation::query()
                ->with(['user', 'vehicle', 'service'])
                ->forCenter($centerId)
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
