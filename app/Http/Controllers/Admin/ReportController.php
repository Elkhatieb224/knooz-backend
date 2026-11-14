<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Operation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $type = $request->string('type')->toString() ?: 'financial';

        if ($type === 'financial') {
            $this->authorize('reports.financial');
        } else {
            $this->authorize('reports.operations');
        }

        $financial = [
            'total' => (float) Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereBetween('created_at', [$from, $to])
                ->sum('amount'),
            'count' => Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'by_method' => Payment::query()
                ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('method')
                ->get(),
        ];

        $centers = Center::query()
            ->withCount([
                'operations as operations_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                'bookings as bookings_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ])
            ->withSum([
                'operations as revenue' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ], 'amount')
            ->orderByDesc('operations_count')
            ->limit(20)
            ->get();

        $customerUsage = User::query()
            ->whereIn('role', ['customer_individual', 'customer_company'])
            ->withCount([
                'operations as operations_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ])
            ->withSum([
                'operations as spent' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
            ], 'amount')
            ->orderByDesc('operations_count')
            ->limit(20)
            ->get();

        $topServices = Operation::query()
            ->select('service_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as revenue'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('service_id')
            ->with('service')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.index', compact(
            'from',
            'to',
            'type',
            'financial',
            'centers',
            'customerUsage',
            'topServices'
        ));
    }
}
