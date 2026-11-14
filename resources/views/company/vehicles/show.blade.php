@extends('layouts.company')

@section('title', $vehicle->plate_number)
@section('breadcrumb', 'تفاصيل المركبة')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">{{ $vehicle->plate_number }}</div>
    <a href="{{ route('company.vehicles.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><strong>بيانات المركبة</strong></div>
            <div class="panel-body">
                <div class="mb-2">الماركة: <strong>{{ $vehicle->brand }}</strong></div>
                <div class="mb-2">الموديل: <strong>{{ $vehicle->model }}</strong></div>
                <div class="mb-2">اللون: <strong>{{ $vehicle->color ?? '—' }}</strong></div>
                <div class="mb-2">السنة: <strong>{{ $vehicle->year ?? '—' }}</strong></div>
                <div class="mb-2">الحالة: @include('components.company.status-badge', ['status' => $vehicle->status])</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><strong>آخر العمليات</strong></div>
            <div class="panel-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>الخدمة</th>
                                <th>المركز</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicle->operations as $op)
                                <tr>
                                    <td>{{ $op->service?->name_ar ?? '—' }}</td>
                                    <td>{{ $op->center?->name ?? '—' }}</td>
                                    <td>{{ number_format((float) $op->amount, 0) }} ر.س</td>
                                    <td>@include('components.company.status-badge', ['status' => $op->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">@include('components.company.empty-state', ['message' => 'لا توجد عمليات'])</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
