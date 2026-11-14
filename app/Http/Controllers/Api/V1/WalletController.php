<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Services\Api\CustomerWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function __construct(protected CustomerWalletService $wallets)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->wallets->ensureWallet($request->user());

        $transactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest()
            ->limit(30)
            ->get();

        return ApiResponse::success([
            'balance' => (float) $wallet->balance,
            'loyalty_points' => (int) $request->user()->loyalty_points,
            'transactions' => $transactions,
        ]);
    }

    public function recharge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:100000'],
            'method' => ['required', Rule::in([
                PaymentMethod::BankTransfer->value,
                PaymentMethod::Online->value,
            ])],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $payment = $this->wallets->requestRecharge(
            $request->user(),
            $data,
            $request->file('receipt')
        );

        return ApiResponse::success($payment, 'تم إرسال طلب الشحن للمراجعة.', 201);
    }

    public function payments(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return ApiResponse::success($payments->items(), 'OK', 200, [
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'total' => $payments->total(),
        ]);
    }
}
