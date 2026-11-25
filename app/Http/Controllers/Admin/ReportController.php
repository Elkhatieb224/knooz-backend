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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $type = $request->string('type')->toString() ?: 'financial';

        if (! in_array($type, ['financial', 'centers', 'customers'], true)) {
            $type = 'financial';
        }

        if ($type === 'financial') {
            $this->authorize('reports.financial');
        } else {
            $this->authorize('reports.operations');
        }

        $financial = ['total' => 0.0, 'count' => 0, 'by_method' => collect()];
        $centers = collect();
        $customerUsage = collect();
        $topServices = collect();

        if ($type === 'financial') {
            $base = Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereBetween('created_at', [$from, $to]);

            $financial = [
                'total' => (float) (clone $base)->sum('amount'),
                'count' => (clone $base)->count(),
                'by_method' => (clone $base)
                    ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('method')
                    ->get(),
            ];
        } elseif ($type === 'centers') {
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
        } else {
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
        }

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

    public function export(Request $request): StreamedResponse
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $type = $request->string('type')->toString() ?: 'financial';

        if ($type === 'financial') {
            $this->authorize('reports.financial');
        } else {
            $this->authorize('reports.operations');
        }

        $filename = sprintf('knooz-report-%s-%s-%s.csv', $type, $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($type, $from, $to) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'financial') {
                fputcsv($out, ['method', 'count', 'total']);
                $rows = Payment::query()
                    ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                    ->whereBetween('created_at', [$from, $to])
                    ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('method')
                    ->get();
                foreach ($rows as $row) {
                    fputcsv($out, [
                        is_object($row->method) ? $row->method->value : (string) $row->method,
                        $row->count,
                        $row->total,
                    ]);
                }
            } elseif ($type === 'centers') {
                fputcsv($out, ['center', 'operations', 'bookings', 'revenue']);
                $rows = Center::query()
                    ->withCount([
                        'operations as operations_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                        'bookings as bookings_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                    ])
                    ->withSum([
                        'operations as revenue' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                    ], 'amount')
                    ->orderByDesc('operations_count')
                    ->get();
                foreach ($rows as $row) {
                    fputcsv($out, [$row->name, $row->operations_count, $row->bookings_count, $row->revenue ?? 0]);
                }
            } else {
                fputcsv($out, ['customer', 'operations', 'spent']);
                $rows = User::query()
                    ->whereIn('role', ['customer_individual', 'customer_company'])
                    ->withCount([
                        'operations as operations_count' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                    ])
                    ->withSum([
                        'operations as spent' => fn ($q) => $q->whereBetween('created_at', [$from, $to]),
                    ], 'amount')
                    ->orderByDesc('operations_count')
                    ->limit(500)
                    ->get();
                foreach ($rows as $row) {
                    fputcsv($out, [$row->name, $row->operations_count, $row->spent ?? 0]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
