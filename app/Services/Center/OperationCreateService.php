<?php

namespace App\Services\Center;

use App\Enums\BookingStatus;
use App\Enums\OperationPaymentStatus;
use App\Enums\OperationStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Booking;
use App\Models\Center;
use App\Models\CenterUser;
use App\Models\Operation;
use App\Models\Subscription;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Notifications\NotificationHooks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationCreateService
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    /**
     * @param  array{
     *   vehicle_id: int,
     *   service_ids: list<int>,
     *   booking_id?: int|null,
     *   payment_mode?: string|null,
     *   notes?: string|null,
     *   source?: string|null,
     *   verification_code?: string|null
     * }  $data
     * @return list<Operation>
     */
    public function create(Center $center, CenterUser $actor, array $data): array
    {
        $vehicle = Vehicle::query()->with(['user', 'company'])->find($data['vehicle_id']);

        if (! $vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['المركبة غير موجودة.'],
            ]);
        }

        if (! $vehicle->user_id) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['المركبة غير مرتبطة بمالك.'],
            ]);
        }

        // D5 stub: ignore verification_code; never hard-fail for missing OTP in v1.
        if (! empty($data['verification_code']) && $vehicle->company_id) {
            // Accepted but unused — verification not enabled yet.
        }

        $serviceIds = array_values(array_unique(array_map('intval', $data['service_ids'] ?? [])));

        if ($serviceIds === []) {
            throw ValidationException::withMessages([
                'service_ids' => ['يجب اختيار خدمة واحدة على الأقل.'],
            ]);
        }

        $booking = null;
        if (! empty($data['booking_id'])) {
            $booking = Booking::query()
                ->whereKey($data['booking_id'])
                ->where('center_id', $center->id)
                ->where('vehicle_id', $vehicle->id)
                ->first();

            if (! $booking) {
                throw ValidationException::withMessages([
                    'booking_id' => ['الحجز غير موجود لهذا المركز/المركبة.'],
                ]);
            }
        }

        $duplicate = Operation::query()
            ->where('center_id', $center->id)
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', [OperationStatus::Pending, OperationStatus::InProgress])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['توجد عملية جارية لهذه المركبة في هذا المركز.'],
            ]);
        }

        $pivots = $center->services()
            ->whereIn('services.id', $serviceIds)
            ->wherePivot('is_available', true)
            ->get()
            ->keyBy('id');

        foreach ($serviceIds as $serviceId) {
            if (! $pivots->has($serviceId)) {
                throw ValidationException::withMessages([
                    'service_ids' => ["الخدمة #{$serviceId} غير متاحة لهذا المركز."],
                ]);
            }
        }

        $source = $data['source'] ?? ($booking ? 'booking' : 'manual');
        $paymentMode = strtolower((string) ($data['payment_mode'] ?? 'cash'));
        $notes = $data['notes'] ?? null;

        return DB::transaction(function () use (
            $center,
            $actor,
            $vehicle,
            $serviceIds,
            $pivots,
            $booking,
            $source,
            $paymentMode,
            $notes,
        ) {
            $operations = [];
            $totalAmount = 0.0;

            foreach ($serviceIds as $index => $serviceId) {
                $service = $pivots->get($serviceId);
                $amount = $service->pivot->contract_price !== null
                    ? (float) $service->pivot->contract_price
                    : (float) $service->base_price;
                $totalAmount += $amount;

                $operations[] = [
                    'booking_id' => ($index === 0 && $booking) ? $booking->id : null,
                    'user_id' => $vehicle->user_id,
                    'vehicle_id' => $vehicle->id,
                    'center_id' => $center->id,
                    'service_id' => $serviceId,
                    'created_by_center_user_id' => $actor->id,
                    'status' => OperationStatus::InProgress,
                    'amount' => $amount,
                    'payment_status' => OperationPaymentStatus::Unpaid,
                    'started_at' => now(),
                    'notes' => $notes,
                    'source' => $source,
                ];
            }

            $paymentStatus = $this->resolvePaymentStatus(
                $paymentMode,
                $vehicle,
                $totalAmount,
                $operations
            );

            $created = [];
            foreach ($operations as $row) {
                $row['payment_status'] = $paymentStatus;
                $operation = Operation::query()->create($row);
                $this->notificationHooks->operationUpdated($operation->fresh(['vehicle']));
                $created[] = $operation->load(['user', 'vehicle', 'service', 'createdByCenterUser', 'images']);
            }

            if ($booking && $booking->status === BookingStatus::New) {
                $booking->update(['status' => BookingStatus::InProgress]);
                $this->notificationHooks->bookingUpdated($booking->fresh(['vehicle']));
            }

            return $created;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     */
    protected function resolvePaymentStatus(
        string $paymentMode,
        Vehicle $vehicle,
        float $totalAmount,
        array $operations,
    ): OperationPaymentStatus {
        return match ($paymentMode) {
            'subscription' => $this->markSubscription($vehicle),
            'credit' => OperationPaymentStatus::Credit,
            'wallet' => $this->tryWalletDebit($vehicle, $totalAmount, $operations),
            'cash' => OperationPaymentStatus::Unpaid,
            default => OperationPaymentStatus::Unpaid,
        };
    }

    protected function markSubscription(Vehicle $vehicle): OperationPaymentStatus
    {
        $active = Subscription::query()
            ->where(function ($q) use ($vehicle) {
                $q->where('user_id', $vehicle->user_id);
                if ($vehicle->company_id) {
                    $q->orWhere('company_id', $vehicle->company_id);
                }
            })
            ->where('status', SubscriptionStatus::Active)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->exists();

        return $active
            ? OperationPaymentStatus::Subscription
            : OperationPaymentStatus::Unpaid;
    }

    /**
     * Best-effort wallet debit using existing Wallet / WalletTransaction models.
     *
     * @param  list<array<string, mixed>>  $operations
     */
    protected function tryWalletDebit(Vehicle $vehicle, float $totalAmount, array $operations): OperationPaymentStatus
    {
        if ($totalAmount <= 0) {
            return OperationPaymentStatus::Free;
        }

        $wallet = Wallet::query()
            ->where('user_id', $vehicle->user_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet || (float) $wallet->balance < $totalAmount) {
            // Do not invent a broken forced debit — leave unpaid.
            return OperationPaymentStatus::Unpaid;
        }

        $newBalance = (float) $wallet->balance - $totalAmount;
        $wallet->update(['balance' => $newBalance]);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransactionType::Debit,
            'amount' => $totalAmount,
            'balance_after' => $newBalance,
            'reference_type' => Operation::class,
            'reference_id' => null,
            'description' => 'خصم عملية مركز #'.$operations[0]['center_id'].' مركبة #'.$vehicle->id,
            'created_by' => null,
        ]);

        return OperationPaymentStatus::Paid;
    }
}
