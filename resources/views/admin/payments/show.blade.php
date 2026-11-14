@extends('layouts.admin')
@section('title', 'تفاصيل دفعة')
@section('breadcrumb', 'تفاصيل دفعة')
@section('content')
<div class="page-title">دفعة #{{ $payment->id }}</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-body">
                <div class="mb-2">العميل: {{ $payment->user?->name ?? '—' }}</div>
                <div class="mb-2">الشركة: {{ $payment->company?->name ?? '—' }}</div>
                <div class="mb-2">المبلغ: <strong>{{ number_format((float)$payment->amount, 2) }} ر.س</strong></div>
                <div class="mb-2">الطريقة: {{ $payment->method_label }}</div>
                <div class="mb-2">الحالة: @include('components.admin.status-badge', ['status' => $payment->status])</div>
                <div class="mb-2">المستند: {{ $payment->receipt_path ?: '—' }}</div>
                <div class="mb-2">المراجع: {{ $payment->reviewer?->name ?: '—' }}</div>
                <div>ملاحظات: {{ $payment->notes ?: '—' }}</div>
            </div>
        </div>
    </div>
    @can('payments.manage')
    @if ($payment->status?->value === 'pending')
    <div class="col-lg-5">
        <div class="panel mb-3">
            <div class="panel-header"><strong>قبول التحويل</strong></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.payments.approve', $payment) }}">
                    @csrf
                    <textarea name="notes" class="form-control mb-2" placeholder="ملاحظة اختيارية"></textarea>
                    <button class="btn btn-success w-100" data-confirm-click="قبول الدفعة وشحن المحفظة؟">قبول وإضافة رصيد</button>
                </form>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><strong>رفض التحويل</strong></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">
                    @csrf
                    <textarea name="notes" class="form-control mb-2" placeholder="سبب الرفض"></textarea>
                    <button class="btn btn-outline-danger w-100" data-confirm-click="رفض الدفعة؟">رفض</button>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endcan
</div>
@endsection
