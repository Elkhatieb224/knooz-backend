@extends('layouts.admin')
@section('title', $vehicle->plate_number)
@section('breadcrumb', 'تفاصيل مركبة')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="page-title mb-1">{{ $vehicle->display_name }}</div>
        <div class="text-muted">{{ $vehicle->user?->name ?? $vehicle->company?->name }}</div>
    </div>
    @can('vehicles.manage')
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.vehicles.regenerate-codes', $vehicle) }}">@csrf<button class="btn btn-outline-primary">توليد QR/NFC</button></form>
        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-primary">تعديل</a>
        <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" data-confirm="حذف المركبة؟">@csrf @method('DELETE')<button class="btn btn-outline-danger">حذف</button></form>
    </div>
    @endcan
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="panel"><div class="panel-body">
            <div class="mb-2">الحالة: @include('components.admin.status-badge', ['status' => $vehicle->status])</div>
            <div class="mb-2">QR: <code>{{ $vehicle->qr_code }}</code></div>
            <div class="mb-2">NFC: <code>{{ $vehicle->nfc_code }}</code></div>
            <div>{{ $vehicle->color }} · {{ $vehicle->year }}</div>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="panel">
            <div class="panel-header"><strong>سجل العمليات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                    @forelse ($vehicle->operations as $op)
                        <tr>
                            <td>{{ $op->service?->name_ar }}</td>
                            <td>{{ number_format((float)$op->amount, 2) }}</td>
                            <td>@include('components.admin.status-badge', ['status' => $op->status])</td>
                            <td>{{ $op->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد عمليات'])</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
