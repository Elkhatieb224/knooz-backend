<?php

namespace App\Http\Controllers\Center;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Center\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Services\Notifications\NotificationHooks;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): View
    {
        $centerId = Tenant::centerId();

        $bookings = Booking::query()
            ->with(['user', 'vehicle', 'service'])
            ->forCenter($centerId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', $term));
                });
            })
            ->latest('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        return view('center.bookings.index', [
            'bookings' => $bookings,
            'bookingStatuses' => BookingStatus::labels(),
        ]);
    }

    public function update(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->center_id === Tenant::centerId(), 404);

        $booking->update($request->validated());
        $this->notificationHooks->bookingUpdated($booking->fresh(['vehicle']));

        return back()->with('success', 'تم تحديث حالة الحجز.');
    }
}
