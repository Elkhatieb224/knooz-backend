<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        $this->authorize('dashboard.view');

        return view('admin.dashboard.index', [
            'stats' => $this->dashboard->stats(),
            'recentOperations' => $this->dashboard->recentOperations(),
            'revenueChart' => $this->dashboard->revenueChart('monthly'),
            'serviceDistribution' => $this->dashboard->serviceDistribution(),
            'fastestCenters' => $this->dashboard->fastestCenters(),
            'userActivity' => $this->dashboard->userActivity(),
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $this->authorize('dashboard.view');

        $period = $request->string('period')->toString() ?: 'monthly';

        return response()->json($this->dashboard->revenueChart($period));
    }
}
