@extends('layouts.admin')
@section('title', 'تفاصيل التحويل #'.$payment->id)
@section('breadcrumb', 'تفاصيل التحويل')
@section('content')
@php
    $isPending = $payment->status?->value === 'pending';
    $ownerName = $payment->user?->name ?? $payment->company?->name ?? '—';
    $walletBalance = $payment->wallet?->balance
        ?? $payment->user?->wallet?->balance
        ?? $payment->company?->wallet?->balance
        ?? null;
@endphp

<div class="payment-show">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <div class="page-title mb-0">تحويل / دفعة #{{ $payment->id }}</div>
            <div class="text-muted small">
                تاريخ الطلب: <span class="dir-ltr">{{ $payment->created_at?->format('Y-m-d H:i') }}</span>
                @if ($payment->reviewed_at)
                    · تمت المراجعة: <span class="dir-ltr">{{ $payment->reviewed_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @include('components.admin.status-badge', ['status' => $payment->status])
            <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-knooz">رجوع للقائمة</a>
        </div>
    </div>

    <div class="row g-2 align-items-start">
        <div class="col-lg-8">
            <div class="panel mb-2">
                <div class="panel-body py-3">
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2">
                        <div class="payment-amount-hero">
                            <div class="payment-amount-value dir-ltr">{{ number_format((float) $payment->amount, 2) }}</div>
                            <div class="payment-amount-currency">{{ $currency }}</div>
                        </div>
                        <div class="text-muted small">{{ $payment->method_label }} · {{ $payment->status_label }}</div>
                    </div>
                    <div class="payment-meta-grid">
                        <div>
                            <div class="payment-meta-label">رصيد المحفظة</div>
                            <div class="payment-meta-value dir-ltr">
                                {{ $walletBalance !== null ? number_format((float) $walletBalance, 2).' '.$currency : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="payment-meta-label">صاحب الطلب</div>
                            <div class="payment-meta-value">{{ $ownerName }}</div>
                        </div>
                        <div>
                            <div class="payment-meta-label">النوع</div>
                            <div class="payment-meta-value">{{ $payment->user ? 'عميل فردي' : ($payment->company ? 'شركة' : '—') }}</div>
                        </div>
                        <div>
                            <div class="payment-meta-label">الجوال</div>
                            <div class="payment-meta-value dir-ltr">{{ $payment->user?->phone ?: $payment->company?->phone ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="payment-meta-label">البريد</div>
                            <div class="payment-meta-value dir-ltr">{{ $payment->user?->email ?: $payment->company?->email ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="payment-meta-label">المحفظة</div>
                            <div class="payment-meta-value dir-ltr">
                                @if ($payment->wallet_id)
                                    <a href="{{ route('admin.wallets.show', $payment->wallet_id) }}">#{{ $payment->wallet_id }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if ($payment->user)
                            @can('users.view')
                                <a href="{{ route('admin.users.show', $payment->user) }}" class="btn btn-sm btn-outline-knooz">عرض العميل</a>
                            @endcan
                        @endif
                        @if ($payment->company)
                            @can('companies.view')
                                <a href="{{ route('admin.companies.show', $payment->company) }}" class="btn btn-sm btn-outline-knooz">عرض الشركة</a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            <div class="panel mb-2">
                <div class="panel-header py-2"><strong>ملاحظات ومراجعة</strong></div>
                <div class="panel-body py-3">
                    <div class="payment-notes-box mb-2">{{ $payment->notes ?: 'لا توجد ملاحظات مسجّلة.' }}</div>
                    <div class="payment-meta-grid">
                        <div>
                            <div class="payment-meta-label">المراجع</div>
                            <div class="payment-meta-value">{{ $payment->reviewer?->name ?: ($payment->reviewed_at ? 'مراجع غير مرتبط' : '—') }}</div>
                        </div>
                        <div>
                            <div class="payment-meta-label">وقت المراجعة</div>
                            <div class="payment-meta-value dir-ltr">{{ $payment->reviewed_at?->format('Y-m-d H:i') ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel mb-2">
                <div class="panel-header py-2 d-flex justify-content-between align-items-center gap-2">
                    <strong>إيصال التحويل</strong>
                    @if ($receipt['exists'])
                        <div class="d-flex gap-2">
                            <a href="{{ $receipt['url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> عرض
                            </a>
                            <a href="{{ $receipt['download_url'] }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i> تحميل
                            </a>
                        </div>
                    @endif
                </div>
                <div class="panel-body py-3">
                    @if ($receipt['exists'])
                        <div class="d-flex flex-wrap gap-3 mb-2 small text-muted">
                            <span><i class="bi bi-file-earmark"></i> {{ $receipt['name'] }}</span>
                            <span>{{ $receipt['size'] }}</span>
                            <span class="dir-ltr">{{ $receipt['mime'] ?: 'ملف' }}</span>
                        </div>
                        @if ($receipt['is_image'])
                            <div class="payment-receipt-preview">
                                <a href="{{ $receipt['url'] }}" target="_blank" rel="noopener">
                                    <img src="{{ $receipt['url'] }}" alt="إيصال التحويل" class="img-fluid rounded border">
                                </a>
                            </div>
                        @elseif ($receipt['is_pdf'])
                            <div class="payment-receipt-preview">
                                <iframe src="{{ $receipt['url'] }}" title="إيصال PDF" class="payment-receipt-frame"></iframe>
                            </div>
                        @else
                            <div class="payment-receipt-file">
                                <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
                                <div class="mt-1 small">ملف مرفق — استخدم العرض أو التحميل أعلاه.</div>
                            </div>
                        @endif
                    @else
                        <div class="text-muted small py-2">لم يتم إرفاق إيصال مع هذا الطلب.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('payments.manage')
                @if ($isPending)
                    <div class="panel mb-2 border-success-subtle">
                        <div class="panel-header py-2"><strong class="text-success">قبول التحويل</strong></div>
                        <div class="panel-body py-3">
                            <p class="small text-muted mb-2">سيتم شحن المحفظة وإرسال إشعار للعميل.</p>
                            <form method="POST" action="{{ route('admin.payments.approve', $payment) }}">
                                @csrf
                                <label class="form-label mb-1">تعليق للعميل (اختياري)</label>
                                <textarea name="notes" class="form-control mb-2 @error('notes') is-invalid @enderror" rows="2" placeholder="مثال: تم التحقق من التحويل بنجاح">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                @enderror
                                <button class="btn btn-success w-100" data-confirm-click="قبول الدفعة وشحن المحفظة وإشعار العميل؟">
                                    <i class="bi bi-check2-circle"></i> قبول وإضافة الرصيد
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="panel mb-2 border-danger-subtle">
                        <div class="panel-header py-2"><strong class="text-danger">رفض التحويل</strong></div>
                        <div class="panel-body py-3">
                            <p class="small text-muted mb-2">سبب الرفض إلزامي ويظهر في إشعار العميل.</p>
                            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">
                                @csrf
                                <label class="form-label mb-1">سبب الرفض <span class="text-danger">*</span></label>
                                <textarea name="notes" class="form-control mb-2 @error('notes') is-invalid @enderror" rows="3" required placeholder="مثال: صورة الإيصال غير واضحة">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                @enderror
                                <button class="btn btn-outline-danger w-100" data-confirm-click="رفض الدفعة وإرسال السبب للعميل؟">
                                    <i class="bi bi-x-circle"></i> رفض وإشعار العميل
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="panel mb-2">
                        <div class="panel-header py-2"><strong>إجراء المراجعة</strong></div>
                        <div class="panel-body py-3">
                            <div class="alert alert-light border mb-0 py-2 small">
                                تمت مراجعة هذه الدفعة مسبقاً — الحالة:
                                <strong>{{ $payment->status_label }}</strong>.
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="panel mb-2">
                    <div class="panel-header py-2"><strong>صلاحية المراجعة</strong></div>
                    <div class="panel-body py-3 text-muted small">
                        لديك صلاحية العرض فقط. قبول/رفض التحويلات لمن لديه صلاحية إدارة المدفوعات.
                    </div>
                </div>
            @endcan

            <div class="panel">
                <div class="panel-header py-2"><strong>ملخص سريع</strong></div>
                <div class="panel-body py-3 small">
                    <div class="d-flex justify-content-between py-1 border-bottom"><span>رقم الدفعة</span><strong class="dir-ltr">#{{ $payment->id }}</strong></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span>المبلغ</span><strong class="dir-ltr">{{ number_format((float) $payment->amount, 2) }} {{ $currency }}</strong></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span>الطريقة</span><strong>{{ $payment->method_label }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>إيصال</span><strong>{{ $receipt['exists'] ? 'مرفق' : 'غير مرفق' }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.payment-show .panel {
  height: auto;
}
.payment-show .page-title {
  margin-bottom: .15rem;
  font-size: 1.2rem;
}
.payment-amount-hero {
  display: flex;
  align-items: baseline;
  gap: .4rem;
}
.payment-amount-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--primary);
  line-height: 1;
}
.payment-amount-currency {
  font-size: .9rem;
  color: var(--text-muted);
  font-weight: 600;
}
.payment-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: .65rem .9rem;
}
.payment-meta-label {
  font-size: .72rem;
  color: var(--text-muted);
  margin-bottom: .1rem;
}
.payment-meta-value {
  font-weight: 700;
  font-size: .92rem;
  line-height: 1.35;
  word-break: break-word;
}
.payment-notes-box {
  white-space: pre-wrap;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: .55rem .75rem;
  line-height: 1.55;
  font-size: .9rem;
}
.payment-receipt-preview {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: .5rem;
  text-align: center;
}
.payment-receipt-preview img {
  max-height: 280px;
  object-fit: contain;
}
.payment-receipt-frame {
  width: 100%;
  min-height: 260px;
  border: 0;
  border-radius: 6px;
  background: #fff;
}
.payment-receipt-file {
  text-align: center;
  padding: .75rem .5rem;
  color: var(--text-muted);
}
</style>
@endpush
