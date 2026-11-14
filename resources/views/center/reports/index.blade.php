@extends('layouts.center')

@section('title', 'التقارير')
@section('breadcrumb', 'التقارير')

@section('content')
<div class="page-title">التقارير</div>
<p class="text-muted mb-4">أداء المركز خلال الشهر الحالي</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'عمليات الشهر', 'value' => $monthOperations, 'icon' => 'bi-wrench'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'مكتملة', 'value' => $monthCompleted, 'icon' => 'bi-check2-circle'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'إيراد مكتمل', 'value' => $monthRevenue, 'icon' => 'bi-cash-coin', 'format' => 'money'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'حجوزات الشهر', 'value' => $monthBookings, 'icon' => 'bi-calendar-check'])
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-header"><strong>العمليات حسب الحالة</strong></div>
            <div class="panel-body">
                @forelse ($operationStatuses as $value => $label)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $label }}</span>
                        <span class="fw-bold">{{ (int) ($operationsByStatus[$value] ?? 0) }}</span>
                    </div>
                @empty
                    @include('components.center.empty-state', ['message' => 'لا توجد بيانات'])
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-header"><strong>الحجوزات حسب الحالة</strong></div>
            <div class="panel-body">
                @forelse ($bookingStatuses as $value => $label)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $label }}</span>
                        <span class="fw-bold">{{ (int) ($bookingsByStatus[$value] ?? 0) }}</span>
                    </div>
                @empty
                    @include('components.center.empty-state', ['message' => 'لا توجد بيانات'])
                @endforelse
                <div class="d-flex justify-content-between align-items-center py-2 mt-2">
                    <span class="text-muted">ملغاة هذا الشهر</span>
                    <span class="fw-bold text-danger">{{ $cancelledBookings }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
