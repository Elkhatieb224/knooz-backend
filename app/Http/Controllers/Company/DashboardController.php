<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Company\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        $company = auth('company')->user()->company()->with('wallet')->firstOrFail();

        return view('company.dashboard.index', [
            'company' => $company,
            'stats' => $this->dashboard->stats($company),
            'recentOperations' => $this->dashboard->recentOperations($company),
        ]);
    }
}
