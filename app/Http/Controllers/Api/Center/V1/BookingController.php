<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Center\UpdateBookingRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\BookingResource;
use App\Models\Booking;
use App\Services\Notifications\NotificationHooks;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $centerId = Tenant::centerId();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 15)));

        $bookings = Booking::query()
            ->with(['user', 'vehicle', 'service'])
            ->forCenter($centerId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('scheduled_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('scheduled_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
                });
            })
            ->latest('scheduled_at')
            ->paginate($perPage);

        return ApiResponse::success(
            BookingResource::collection($bookings)->resolve(),
            'OK',
            200,
            [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ]
        );
    }

    public function show(int $id): JsonResponse
    {
        $booking = $this->findOwned($id);

        return ApiResponse::success(new BookingResource($booking));
    }

    public function update(UpdateBookingRequest $request, int $id): JsonResponse
    {
        $booking = $this->findOwned($id);
        $status = BookingStatus::from($request->validated('status'));
        $notes = $request->validated('notes');

        if (! $this->canTransition($booking->status, $status)) {
            return ApiResponse::error('انتقال الحالة غير مسموح.', 409);
        }

        if ($status === BookingStatus::Cancelled && ($notes === null || trim($notes) === '')) {
            return ApiResponse::error('ملاحظات الرفض مطلوبة عند الإلغاء.', 422, [
                'notes' => ['أضف سبب الرفض.'],
            ]);
        }

        $payload = ['status' => $status];
        if ($notes !== null) {
            $payload['notes'] = $notes;
        }

        $booking->update($payload);
        $this->notificationHooks->bookingUpdated($booking->fresh(['vehicle']));

        return ApiResponse::success(
            new BookingResource($booking->fresh(['user', 'vehicle', 'service'])),
            'تم تحديث حالة الحجز.'
        );
    }

    protected function findOwned(int $id): Booking
    {
        return Booking::query()
            ->with(['user', 'vehicle', 'service'])
            ->forCenter(Tenant::centerId())
            ->whereKey($id)
            ->firstOrFail();
    }

    protected function canTransition(?BookingStatus $from, BookingStatus $to): bool
    {
        if (! $from) {
            return false;
        }

        return match ($from) {
            BookingStatus::New => in_array($to, [BookingStatus::InProgress, BookingStatus::Cancelled], true),
            BookingStatus::InProgress => in_array($to, [BookingStatus::Completed, BookingStatus::Cancelled], true),
            BookingStatus::Completed, BookingStatus::Cancelled => false,
        };
    }
}
