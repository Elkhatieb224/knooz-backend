<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ComplaintStatus;
use App\Enums\EmergencyRequestStatus;
use App\Enums\OperationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\EmergencyRequest;
use App\Models\Operation;
use App\Services\Notifications\NotificationHooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RequestHubController extends Controller
{
    public function __construct(protected NotificationHooks $notificationHooks)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('bookings.view');

        $tab = $request->string('tab')->toString() ?: 'bookings';

        $data = match ($tab) {
            'operations' => [
                'items' => Operation::query()
                    ->with(['user', 'vehicle', 'center', 'service'])
                    ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('q'), function ($q) use ($request) {
                        $term = '%'.$request->string('q').'%';
                        $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term));
                    })
                    ->latest()
                    ->paginate(15)
                    ->withQueryString(),
            ],
            'complaints' => [
                'items' => Complaint::query()
                    ->with(['user', 'assignee'])
                    ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('q'), function ($q) use ($request) {
                        $term = '%'.$request->string('q').'%';
                        $q->where(function ($inner) use ($term) {
                            $inner->where('subject', 'like', $term)
                                ->orWhere('body', 'like', $term);
                        });
                    })
                    ->latest()
                    ->paginate(15)
                    ->withQueryString(),
            ],
            'emergency' => [
                'items' => EmergencyRequest::query()
                    ->with(['user', 'vehicle', 'employee'])
                    ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('q'), function ($q) use ($request) {
                        $term = '%'.$request->string('q').'%';
                        $q->where(function ($inner) use ($term) {
                            $inner->where('address', 'like', $term)
                                ->orWhere('notes', 'like', $term);
                        });
                    })
                    ->latest()
                    ->paginate(15)
                    ->withQueryString(),
            ],
            default => [
                'items' => Booking::query()
                    ->with(['user', 'vehicle', 'center', 'service'])
                    ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                    ->when($request->filled('q'), function ($q) use ($request) {
                        $term = '%'.$request->string('q').'%';
                        $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term));
                    })
                    ->latest()
                    ->paginate(15)
                    ->withQueryString(),
            ],
        };

        return view('admin.requests.index', [
            'tab' => in_array($tab, ['bookings', 'operations', 'complaints', 'emergency'], true) ? $tab : 'bookings',
            'items' => $data['items'],
            'bookingStatuses' => BookingStatus::labels(),
            'operationStatuses' => OperationStatus::labels(),
            'complaintStatuses' => ComplaintStatus::labels(),
            'emergencyStatuses' => EmergencyRequestStatus::labels(),
        ]);
    }

    public function updateBooking(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('bookings.manage');

        $data = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $booking->update($data);
        $this->notificationHooks->bookingUpdated($booking->fresh(['vehicle']));

        return back()->with('success', 'تم تحديث حالة الحجز.');
    }

    public function updateOperation(Request $request, Operation $operation): RedirectResponse
    {
        $this->authorize('bookings.manage');

        $data = $request->validate([
            'status' => ['required', Rule::enum(OperationStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $operation->update($data);
        $this->notificationHooks->operationUpdated($operation->fresh(['vehicle']));

        return back()->with('success', 'تم تحديث حالة العملية.');
    }

    public function updateComplaint(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('complaints.manage');

        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $complaint->update($data);
        $this->notificationHooks->complaintUpdated($complaint->fresh());

        return back()->with('success', 'تم تحديث الشكوى.');
    }

    public function updateEmergency(Request $request, EmergencyRequest $emergencyRequest): RedirectResponse
    {
        $this->authorize('bookings.manage');

        $data = $request->validate([
            'status' => ['required', Rule::enum(EmergencyRequestStatus::class)],
            'notes' => ['nullable', 'string'],
            'employee_id' => ['nullable', 'exists:users,id'],
        ]);

        $emergencyRequest->update($data);
        $this->notificationHooks->emergencyUpdated($emergencyRequest->fresh());

        return back()->with('success', 'تم تحديث طلب الطوارئ.');
    }
}
