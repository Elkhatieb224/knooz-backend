<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Admin\PaymentService;
use App\Services\Notifications\NotificationHooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $payment->load(['user.wallet', 'company.wallet', 'wallet', 'reviewer']);

        $receipt = $this->receiptMeta($payment);

        return view('admin.payments.show', [
            'payment' => $payment,
            'receipt' => $receipt,
            'currency' => Setting::getValue('currency', 'ر.س'),
        ]);
    }

    public function receipt(Payment $payment, Request $request): BinaryFileResponse|StreamedResponse
    {
        $this->authorize('payments.view');

        $path = $this->safeReceiptPath($payment);
        abort_unless($path !== null, 404);

        $disk = Storage::disk('public');
        $filename = basename($path);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        if ($request->boolean('download')) {
            return $disk->download($path, $filename, [
                'Content-Type' => $mime,
            ]);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('payments.manage');

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'notes' => 'تعليق للعميل',
        ]);

        $comment = trim((string) ($data['notes'] ?? ''));
        $comment = $comment === '' ? null : $comment;

        try {
            $payment = $this->paymentService->approve($payment, $request->user('admin'), $comment);
            $this->notificationHooks->paymentReviewed($payment, true, $comment);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'تم قبول الدفعة وإضافة الرصيد للمحفظة وإشعار العميل.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('payments.manage');

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'notes.required' => 'يجب كتابة سبب الرفض ليصل للعميل.',
            'notes.min' => 'سبب الرفض قصير جداً.',
        ], [
            'notes' => 'سبب الرفض',
        ]);

        $comment = trim($data['notes']);

        try {
            $payment = $this->paymentService->reject($payment, $request->user('admin'), $comment);
            $this->notificationHooks->paymentReviewed($payment, false, $comment);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'تم رفض الدفعة وإشعار العميل بالسبب.');
    }

    /**
     * @return array{exists: bool, path: ?string, url: ?string, download_url: ?string, is_image: bool, is_pdf: bool, name: ?string, size: ?string, mime: ?string}
     */
    protected function receiptMeta(Payment $payment): array
    {
        $path = $this->safeReceiptPath($payment);

        if ($path === null) {
            return [
                'exists' => false,
                'path' => null,
                'url' => null,
                'download_url' => null,
                'is_image' => false,
                'is_pdf' => false,
                'name' => null,
                'size' => null,
                'mime' => null,
            ];
        }

        $disk = Storage::disk('public');
        $mime = $disk->mimeType($path) ?: null;
        $size = $disk->size($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return [
            'exists' => true,
            'path' => $path,
            'url' => route('admin.payments.receipt', $payment),
            'download_url' => route('admin.payments.receipt', ['payment' => $payment, 'download' => 1]),
            'is_image' => is_string($mime) && str_starts_with($mime, 'image/'),
            'is_pdf' => $mime === 'application/pdf' || $ext === 'pdf',
            'name' => basename($path),
            'size' => $this->humanFileSize($size),
            'mime' => $mime,
        ];
    }

    protected function safeReceiptPath(Payment $payment): ?string
    {
        $raw = trim((string) $payment->receipt_path);
        if ($raw === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $raw), '/');
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    protected function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
