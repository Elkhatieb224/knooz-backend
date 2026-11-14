<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Admin\PaymentService;
use App\Services\Notifications\NotificationHooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected NotificationHooks $notificationHooks,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('payments.view');

        $payments = Payment::query()
            ->with(['user', 'company', 'reviewer'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('notes', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('payments.view');

        $payment->load(['user', 'company', 'wallet', 'reviewer']);

        return view('admin.payments.show', compact('payment'));
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('payments.manage');

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        try {
            $payment = $this->paymentService->approve($payment, $request->user(), $data['notes'] ?? null);
            $this->notificationHooks->paymentReviewed($payment, true);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم قبول الدفعة وإضافة الرصيد للمحفظة.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('payments.manage');

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        try {
            $payment = $this->paymentService->reject($payment, $request->user(), $data['notes'] ?? null);
            $this->notificationHooks->paymentReviewed($payment, false);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'تم رفض الدفعة.');
    }
}
