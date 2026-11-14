<?php

namespace App\Http\Controllers\Company;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Support\Tenant;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $companyId = Tenant::companyId();

        $subscription = Subscription::query()
            ->with('package')
            ->forCompany($companyId)
            ->where('status', SubscriptionStatus::Active)
            ->latest('starts_at')
            ->first();

        $history = Subscription::query()
            ->with('package')
            ->forCompany($companyId)
            ->latest('starts_at')
            ->paginate(10);

        return view('company.subscriptions.index', compact('subscription', 'history'));
    }
}
