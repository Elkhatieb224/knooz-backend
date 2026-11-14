<?php

namespace App\Services\Admin;

use App\Enums\CenterStatus;
use App\Enums\ComplaintStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Center;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Operation;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function stats(): array
    {
        $now = now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $usersTotal = User::query()
            ->whereIn('role', [UserRole::CustomerIndividual->value, UserRole::CustomerCompany->value])
            ->count();
        $usersPrev = User::query()
            ->whereIn('role', [UserRole::CustomerIndividual->value, UserRole::CustomerCompany->value])
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();

        $centersActive = Center::query()->where('status', CenterStatus::Active)->count();
        $centersPrev = Center::query()
            ->where('status', CenterStatus::Active)
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();

        $companiesTotal = Company::query()->count();
        $companiesPrev = Company::query()->where('created_at', '<=', $lastMonthEnd)->count();

        $revenue = (float) Payment::query()
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
            ->sum('amount');
        $revenueThisMonth = (float) Payment::query()
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
            ->where('created_at', '>=', $thisMonthStart)
            ->sum('amount');
        $revenueLastMonth = (float) Payment::query()
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        $complaintsOpen = Complaint::query()
            ->whereIn('status', [ComplaintStatus::Open->value, ComplaintStatus::InProgress->value])
            ->count();
        $complaintsOpenPrev = Complaint::query()
            ->whereIn('status', [ComplaintStatus::Open->value, ComplaintStatus::InProgress->value])
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();

        return [
            'users' => [
                'value' => $usersTotal,
                'change' => $this->percentChange($usersTotal, $usersPrev),
            ],
            'centers' => [
                'value' => $centersActive,
                'change' => $this->percentChange($centersActive, $centersPrev),
            ],
            'companies' => [
                'value' => $companiesTotal,
                'change' => $this->percentChange($companiesTotal, $companiesPrev),
            ],
            'revenue' => [
                'value' => $revenue,
                'change' => $this->percentChange($revenueThisMonth, $revenueLastMonth),
            ],
            'complaints' => [
                'value' => $complaintsOpen,
                'change' => $this->percentChange($complaintsOpen, $complaintsOpenPrev),
            ],
        ];
    }

    public function recentOperations(int $limit = 8): Collection
    {
        return Operation::query()
            ->with(['service', 'vehicle', 'center', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function revenueChart(string $period = 'monthly'): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';

        return match ($period) {
            'daily' => $this->chartByDays(14),
            'weekly' => $this->chartByWeeks(8),
            'yearly' => $this->chartByYears(5),
            default => $this->chartByMonths(12),
        };
    }

    public function serviceDistribution(): array
    {
        $rows = Operation::query()
            ->select('service_id', DB::raw('COUNT(*) as total'))
            ->groupBy('service_id')
            ->with('service')
            ->get();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row->service?->name_ar ?? 'غير محدد';
            $values[] = (int) $row->total;
        }

        if ($labels === []) {
            $services = Service::query()->where('is_active', true)->get();
            foreach ($services as $service) {
                $labels[] = $service->name_ar;
                $values[] = 0;
            }
        }

        return compact('labels', 'values');
    }

    public function fastestCenters(int $limit = 5): Collection
    {
        return Center::query()
            ->withCount(['operations' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('operations_count')
            ->limit($limit)
            ->get();
    }

    public function userActivity(): array
    {
        $days = collect(range(6, 0))->map(function (int $i) {
            $day = now()->subDays($i)->startOfDay();

            return [
                'label' => $day->translatedFormat('D'),
                'count' => User::query()
                    ->whereDate('last_login_at', $day->toDateString())
                    ->count(),
            ];
        });

        return [
            'labels' => $days->pluck('label')->all(),
            'values' => $days->pluck('count')->all(),
        ];
    }

    protected function percentChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function chartByMonths(int $count): array
    {
        $labels = [];
        $values = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M');
            $values[] = (float) Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        }

        return compact('labels', 'values');
    }

    protected function chartByDays(int $count): array
    {
        $labels = [];
        $values = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->format('d/m');
            $values[] = (float) Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereDate('created_at', $day->toDateString())
                ->sum('amount');
        }

        return compact('labels', 'values');
    }

    protected function chartByWeeks(int $count): array
    {
        $labels = [];
        $values = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = $start->copy()->endOfWeek();
            $labels[] = 'أ'.$start->weekOfYear;
            $values[] = (float) Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
        }

        return compact('labels', 'values');
    }

    protected function chartByYears(int $count): array
    {
        $labels = [];
        $values = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $labels[] = (string) $year;
            $values[] = (float) Payment::query()
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Approved->value])
                ->whereYear('created_at', $year)
                ->sum('amount');
        }

        return compact('labels', 'values');
    }
}
