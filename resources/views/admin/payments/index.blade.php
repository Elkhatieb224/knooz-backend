@extends('layouts.admin')
@section('title', 'المدفوعات')
@section('breadcrumb', 'المحافظ والمدفوعات')
@section('search_action', route('admin.payments.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">المدفوعات</div>
    <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-knooz">المحافظ</a>
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Enums\PaymentStatus::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="method" class="form-select">
            <option value="">كل الطرق</option>
            @foreach (\App\Enums\PaymentMethod::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('method')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>العميل/الشركة</th><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->user?->name ?? $payment->company?->name ?? '—' }}</td>
                    <td class="fw-bold dir-ltr">{{ number_format((float)$payment->amount, 2) }} ر.س</td>
                    <td>{{ $payment->method_label }}</td>
                    <td>@include('components.admin.status-badge', ['status' => $payment->status])</td>
                    <td class="dir-ltr">{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="col-actions"><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary">عرض</a></td>
                </tr>
            @empty
                <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد مدفوعات'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($payments->hasPages())<div class="panel-body">{{ $payments->links() }}</div>@endif
</div>
@endsection
