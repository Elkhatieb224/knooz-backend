@extends('layouts.center')

@section('title', 'لوحة الرئيسية')
@section('breadcrumb', 'لوحة الرئيسية')

@section('content')
<div class="page-title">لوحة الرئيسية</div>
<p class="text-muted mb-4">{{ $center?->name ?? 'مركز الخدمة' }}</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'حجوزات اليوم', 'value' => $stats['bookings_today'], 'icon' => 'bi-calendar-check'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'عمليات جارية', 'value' => $stats['operations_in_progress'], 'icon' => 'bi-hourglass-split'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'مكتمل اليوم', 'value' => $stats['operations_completed_today'], 'icon' => 'bi-check2-circle'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.center.stat-card', ['label' => 'الموظفون', 'value' => $stats['staff_count'], 'icon' => 'bi-people'])
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header">
                <strong>حجوزات اليوم</strong>
                <a href="{{ route('center.bookings.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
            </div>
            <div class="panel-body">
                @forelse ($todayBookings as $booking)
                    <div class="ops-item">
                        <div class="ops-icon"><i class="bi bi-calendar-check"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold">{{ $booking->user?->name ?? '—' }}</div>
                            <div class="small text-muted text-truncate">
                                {{ $booking->vehicle?->plate_number ?? '—' }} · {{ $booking->service?->name_ar ?? '—' }}
                            </div>
                        </div>
                        <div class="ops-meta">
                            @include('components.center.status-badge', ['status' => $booking->status])
                            <div class="small text-muted mt-1">{{ $booking->scheduled_at?->format('H:i') }}</div>
                        </div>
                    </div>
                @empty
                    @include('components.center.empty-state', ['message' => 'لا توجد حجوزات اليوم'])
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header">
                <strong>آخر العمليات</strong>
                <a href="{{ route('center.operations.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
            </div>
            <div class="panel-body">
                @forelse ($recentOperations as $op)
                    <div class="ops-item">
                        <div class="ops-icon"><i class="bi bi-wrench"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold">{{ number_format((float) $op->amount, 0) }} ر.س</div>
                            <div class="small text-muted text-truncate">
                                {{ $op->vehicle?->plate_number ?? '—' }} · {{ $op->service?->name_ar ?? '—' }}
                            </div>
                        </div>
                        <div class="ops-meta">
                            @include('components.center.status-badge', ['status' => $op->status])
                            <div class="small text-muted mt-1">{{ $op->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    @include('components.center.empty-state', ['message' => 'لا توجد عمليات'])
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
