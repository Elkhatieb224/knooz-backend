<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Booking;
use App\Services\Api\CustomerBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(protected CustomerBookingService $bookings)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()
            ->with(['vehicle', 'center', 'service'])
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('scheduled_at');

        $paginator = $query->paginate(min(50, max(1, (int) $request->integer('per_page', 20))));

        $counts = [
            'upcoming' => Booking::query()->where('user_id', $request->user()->id)->whereIn('status', [BookingStatus::New, BookingStatus::InProgress])->count(),
            'completed' => Booking::query()->where('user_id', $request->user()->id)->where('status', BookingStatus::Completed)->count(),
            'cancelled' => Booking::query()->where('user_id', $request->user()->id)->where('status', BookingStatus::Cancelled)->count(),
        ];

        return ApiResponse::success([
            'items' => $paginator->items(),
            'counts' => $counts,
        ], 'OK', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'center_id' => ['required', 'integer', 'exists:centers,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $booking = $this->bookings->create($request->user(), $data);

        return ApiResponse::success($booking, 'تم إنشاء الحجز.', 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 404);

        return ApiResponse::success($booking->load(['vehicle', 'center', 'service', 'operation']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['sometimes', 'integer', 'exists:vehicles,id'],
            'center_id' => ['sometimes', 'integer', 'exists:centers,id'],
            'service_id' => ['sometimes', 'integer', 'exists:services,id'],
            'scheduled_at' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $booking = $this->bookings->update($request->user(), $booking, $data);

        return ApiResponse::success($booking, 'تم تحديث تفاصيل الحجز.');
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->bookings->cancel($request->user(), $booking);

        return ApiResponse::success($booking, 'تم إلغاء الحجز.');
    }

    public function slots(Request $request, int $center): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        return ApiResponse::success([
            'center_id' => $center,
            'date' => $data['date'],
            'slots' => $this->bookings->slots($center, $data['date']),
        ]);
    }
}
