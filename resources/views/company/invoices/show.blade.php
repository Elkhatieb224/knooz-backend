@extends('layouts.company')

@section('title', $invoice->number)
@section('breadcrumb', 'تفاصيل الفاتورة')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">{{ $invoice->number }}</div>
    <a href="{{ route('company.invoices.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
</div>

<div class="panel mb-3">
    <div class="panel-body">
        <div class="row g-3">
            <div class="col-md-3">الحالة: @include('components.company.status-badge', ['status' => $invoice->status])</div>
            <div class="col-md-3">من: <strong>{{ $invoice->period_start?->format('Y-m-d') }}</strong></div>
            <div class="col-md-3">إلى: <strong>{{ $invoice->period_end?->format('Y-m-d') }}</strong></div>
            <div class="col-md-3">الإجمالي: <strong>{{ number_format((float) $invoice->total, 0) }} ر.س</strong></div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><strong>بنود الفاتورة</strong></div>
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>المركبة</th>
                        <th>الخدمة</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->vehicle?->plate_number ?? '—' }}</td>
                            <td>{{ $item->service?->name_ar ?? '—' }}</td>
                            <td>{{ number_format((float) $item->amount, 0) }} ر.س</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">@include('components.company.empty-state', ['message' => 'لا توجد بنود'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
