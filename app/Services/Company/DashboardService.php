<?php

namespace App\Services\Company;

use App\Enums\OperationStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\VehicleStatus;
use App\Models\Company;
use App\Models\Operation;
use App\Models\Subscription;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class DashboardService
{
    public function stats(Company $company): array
    {
        $vehiclesCount = Vehicle::query()->where('company_id', $company->id)->count();
        $activeVehiclesCount = Vehicle::query()
            ->where('company_id', $company->id)
            ->where('status', VehicleStatus::Active)
            ->count();

        $subscription = Subscription::query()
            ->with('package')
            ->where('company_id', $company->id)
            ->where('status', SubscriptionStatus::Active)
            ->latest('starts_at')
            ->first();

        $walletBalance = (float) ($company->wallet?->balance ?? 0);

        $operationsCount = Operation::query()
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $company->id))
            ->count();

        $monthlySpend = (float) Operation::query()
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $company->id))
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', '!=', OperationStatus::Cancelled->value)
            ->sum('amount');

        return [
            'vehicles' => $vehiclesCount,
            'active_vehicles' => $activeVehiclesCount,
            'subscription' => $subscription,
            'wallet_balance' => $walletBalance,
            'operations' => $operationsCount,
            'monthly_spend' => $monthlySpend,
        ];
    }

    public function recentOperations(Company $company, int $limit = 8): Collection
    {
        return Operation::query()
            ->with(['service', 'vehicle', 'center', 'user'])
            ->whereHas('vehicle', fn ($q) => $q->where('company_id', $company->id))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
