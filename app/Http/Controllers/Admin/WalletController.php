<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $wallets = Wallet::query()
            ->with(['user', 'company'])
            ->withCount('transactions')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.wallets', compact('wallets'));
    }

    public function show(Wallet $wallet): View
    {
        $this->authorize('payments.view');

        $wallet->load(['user', 'company']);
        $transactions = $wallet->transactions()->with('creator')->latest()->paginate(20);

        return view('admin.payments.wallet-show', compact('wallet', 'transactions'));
    }
}
