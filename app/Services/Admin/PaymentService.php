<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Enums\WalletTransactionType;
use App\Models\AdminUser;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function approve(Payment $payment, AdminUser|User $reviewer, ?string $notes = null): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new InvalidArgumentException('يمكن قبول المدفوعات قيد المراجعة فقط.');
        }

        $legacyUserId = $this->legacyUserId($reviewer);

        return DB::transaction(function () use ($payment, $notes, $legacyUserId) {
            $payment->update([
                'status' => PaymentStatus::Approved,
                'reviewed_by' => $legacyUserId,
                'reviewed_at' => now(),
                'notes' => $notes ?? $payment->notes,
            ]);

            $wallet = $this->resolveWallet($payment);
            $newBalance = (float) $wallet->balance + (float) $payment->amount;
            $wallet->update(['balance' => $newBalance]);

            if (! $payment->wallet_id) {
                $payment->update(['wallet_id' => $wallet->id]);
            }

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => WalletTransactionType::Credit,
                'amount' => $payment->amount,
                'balance_after' => $newBalance,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => 'قبول تحويل بنكي / شحن رصيد',
                'created_by' => $legacyUserId,
            ]);

            return $payment->fresh(['user', 'company', 'wallet', 'reviewer']);
        });
    }

    public function reject(Payment $payment, AdminUser|User $reviewer, ?string $notes = null): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new InvalidArgumentException('يمكن رفض المدفوعات قيد المراجعة فقط.');
        }

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'reviewed_by' => $this->legacyUserId($reviewer),
            'reviewed_at' => now(),
            'notes' => $notes ?? $payment->notes,
        ]);

        return $payment->fresh(['user', 'company', 'wallet', 'reviewer']);
    }

    protected function legacyUserId(AdminUser|User $reviewer): ?int
    {
        if ($reviewer instanceof User) {
            return $reviewer->id;
        }

        return $reviewer->legacy_user_id;
    }

    protected function resolveWallet(Payment $payment): Wallet
    {
        if ($payment->wallet_id) {
            return Wallet::query()->lockForUpdate()->findOrFail($payment->wallet_id);
        }

        if ($payment->user_id) {
            return Wallet::query()->firstOrCreate(
                ['user_id' => $payment->user_id],
                ['balance' => 0]
            );
        }

        if ($payment->company_id) {
            return Wallet::query()->firstOrCreate(
                ['company_id' => $payment->company_id],
                ['balance' => 0]
            );
        }

        throw new InvalidArgumentException('لا توجد محفظة مرتبطة بهذه الدفعة.');
    }
}
