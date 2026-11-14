<?php

namespace App\Http\Controllers\Api\Company\V1;

use App\Http\Controllers\Api\Company\V1\Concerns\PaginationMeta;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use PaginationMeta;

    public function __invoke(Request $request): JsonResponse
    {
        $wallet = Wallet::query()->where('company_id', Tenant::companyId())->first();

        if (! $wallet) {
            return ApiResponse::success([
                'balance' => 0.0,
                'transactions' => [],
            ], 'OK', 200, [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $this->perPage($request, 20),
                'total' => 0,
            ]);
        }

        $transactions = $wallet->transactions()
            ->latest()
            ->latest('id')
            ->paginate($this->perPage($request, 20));

        return ApiResponse::success([
            'balance' => (float) $wallet->balance,
            'transactions' => $transactions->getCollection()->map(
                fn (WalletTransaction $t) => [
                    'id' => $t->id,
                    'type' => $t->type?->value,
                    'type_label' => $t->type_label,
                    'amount' => (float) $t->amount,
                    'balance_after' => (float) $t->balance_after,
                    'description' => $t->description,
                    'created_at' => optional($t->created_at)?->toIso8601String(),
                ]
            )->values(),
        ], 'OK', 200, $this->meta($transactions));
    }
}
