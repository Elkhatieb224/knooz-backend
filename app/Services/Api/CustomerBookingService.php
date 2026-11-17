<?php

namespace App\Services\Api;

use App\Enums\BookingStatus;
use App\Enums\CenterStatus;
use App\Enums\NotificationType;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Notifications\NotificationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CustomerBookingService
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Booking
    {
        $vehicle = Vehicle::query()
            ->whereKey($data['vehicle_id'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->company_id) {
                    $q->orWhere('company_id', $user->company_id);
                }
            })
            ->where('status', VehicleStatus::Active)
            ->firstOrFail();

        $center = Center::query()
            ->whereKey($data['center_id'])
            ->where('status', CenterStatus::Active)
            ->firstOrFail();

        $service = Service::query()
            ->whereKey($data['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        if ($scheduledAt->lt(now())) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['لا يمكن حجز موعد في الماضي.'],
            ]);
        }

        $price = $data['price'] ?? $service->base_price;

        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'center_id' => $center->id,
            'service_id' => $service->id,
            'scheduled_at' => $scheduledAt,
            'status' => BookingStatus::New,
            'notes' => $data['notes'] ?? null,
            'price' => $price,
        ]);

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::BookingNew,
            'حجز جديد من التطبيق',
            "حجز #{$booking->id} — {$service->name_ar}",
            $booking,
            eventKey: sprintf('booking:%d:created', $booking->id),
            queue: true,
        );

        if ($center->id) {
            $this->notifications->notify(
                $this->notifications->centerUsers((int) $center->id),
                NotificationType::BookingNew,
                'حجز جديد',
                "وصلك حجز جديد #{$booking->id}",
                $booking,
                eventKey: sprintf('booking:%d:center-created', $booking->id),
                queue: false,
            );
        }

        $this->notifications->notify(
            $user,
            NotificationType::BookingNew,
            'تم استلام حجزك',
            "تم تسجيل حجزك #{$booking->id} — {$service->name_ar}. سنتواصل معك قريباً.",
            $booking,
            eventKey: sprintf('booking:%d:customer-created', $booking->id),
            queue: false,
        );

        return $booking->load(['vehicle', 'center', 'service']);
    }

    /**
     * Customer may edit only new (upcoming) bookings.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Booking $booking, array $data): Booking
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($booking->status !== BookingStatus::New) {
            throw ValidationException::withMessages([
                'status' => ['يمكن تعديل الحجوزات الجديدة فقط.'],
            ]);
        }

        $payload = [];

        if (array_key_exists('notes', $data)) {
            $payload['notes'] = $data['notes'];
        }

        if (! empty($data['scheduled_at'])) {
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            if ($scheduledAt->lt(now())) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ['لا يمكن حجز موعد في الماضي.'],
                ]);
            }
            $payload['scheduled_at'] = $scheduledAt;
        }

        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::query()
                ->whereKey($data['vehicle_id'])
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->company_id) {
                        $q->orWhere('company_id', $user->company_id);
                    }
                })
                ->where('status', VehicleStatus::Active)
                ->firstOrFail();
            $payload['vehicle_id'] = $vehicle->id;
        }

        if (! empty($data['center_id'])) {
            $center = Center::query()
                ->whereKey($data['center_id'])
                ->where('status', CenterStatus::Active)
                ->firstOrFail();
            $payload['center_id'] = $center->id;
        }

        if (! empty($data['service_id'])) {
            $service = Service::query()
                ->whereKey($data['service_id'])
                ->where('is_active', true)
                ->firstOrFail();
            $payload['service_id'] = $service->id;
            if (! array_key_exists('price', $data)) {
                $payload['price'] = $service->base_price;
            }
        }

        if (array_key_exists('price', $data) && $data['price'] !== null) {
            $payload['price'] = $data['price'];
        }

        if ($payload === []) {
            return $booking->load(['vehicle', 'center', 'service', 'operation']);
        }

        $booking->update($payload);

        return $booking->fresh(['vehicle', 'center', 'service', 'operation']);
    }

    public function cancel(User $user, Booking $booking): Booking
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($booking->status !== BookingStatus::New) {
            throw ValidationException::withMessages([
                'status' => ['يمكن إلغاء الحجوزات الجديدة فقط.'],
            ]);
        }

        $booking->update(['status' => BookingStatus::Cancelled]);

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::BookingStatus,
            'إلغاء حجز',
            "ألغى العميل الحجز #{$booking->id}",
            $booking,
            eventKey: sprintf('booking:%d:cancelled:admin', $booking->id),
            queue: false,
        );

        if ($booking->center_id) {
            $this->notifications->notify(
                $this->notifications->centerUsers((int) $booking->center_id),
                NotificationType::BookingStatus,
                'إلغاء حجز',
                "أُلغي الحجز #{$booking->id}",
                $booking,
                eventKey: sprintf('booking:%d:cancelled:center', $booking->id),
                queue: false,
            );
        }

        $this->notifications->notify(
            $user,
            NotificationType::BookingStatus,
            'تم إلغاء حجزك',
            "تم إلغاء الحجز #{$booking->id} بنجاح.",
            $booking,
            eventKey: sprintf('booking:%d:cancelled:customer', $booking->id),
            queue: false,
        );

        return $booking->fresh(['vehicle', 'center', 'service']);
    }

    /**
     * @return list<array{time: string, available: bool}>
     */
    public function slots(int $centerId, string $date): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $taken = Booking::query()
            ->where('center_id', $centerId)
            ->whereDate('scheduled_at', $day)
            ->whereIn('status', [BookingStatus::New, BookingStatus::InProgress])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'))
            ->all();

        $slots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $minute) {
                if ($hour === 20 && $minute === 30) {
                    continue;
                }
                $time = sprintf('%02d:%02d', $hour, $minute);
                $slots[] = [
                    'time' => $time,
                    'available' => ! in_array($time, $taken, true),
                ];
            }
        }

        return $slots;
    }
}
