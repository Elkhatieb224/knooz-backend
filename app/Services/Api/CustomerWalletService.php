<?php

namespace App\Services\Api;

use App\Enums\NotificationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerWalletService
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function ensureWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestRecharge(User $user, array $data, ?UploadedFile $receipt = null): Payment
    {
        return DB::transaction(function () use ($user, $data, $receipt) {
            $wallet = $this->ensureWallet($user);

            $path = null;
            if ($receipt) {
                $path = $receipt->store('receipts/'.$user->id, 'public');
            }

            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $data['amount'],
                'method' => PaymentMethod::tryFrom($data['method'] ?? '') ?? PaymentMethod::BankTransfer,
                'status' => PaymentStatus::Pending,
                'receipt_path' => $path,
                'notes' => $data['notes'] ?? ($data['reference'] ?? null),
            ]);

            $this->notifications->notify(
                $this->notifications->activeAdmins('payments.view'),
                NotificationType::PaymentPending,
                'طلب شحن محفظة',
                "عميل {$user->name} طلب شحن بمبلغ {$payment->amount}",
                $payment,
                eventKey: sprintf('payment:%d:created', $payment->id),
                queue: false,
            );

            $this->notifications->notify(
                $user,
                NotificationType::PaymentPending,
                'طلب شحن قيد المراجعة',
                "استلمنا طلب شحن محفظتك بمبلغ {$payment->amount}. سيتم مراجعته قريباً.",
                $payment,
                eventKey: sprintf('payment:%d:customer-created', $payment->id),
                queue: false,
            );

            return $payment->fresh(['wallet']);
        });
    }
}
