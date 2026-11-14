@extends('layouts.company')

@section('title', 'لوحة الرئيسية')
@section('breadcrumb', 'لوحة الرئيسية')

@section('content')
<div class="page-title">لوحة الرئيسية</div>
<p class="text-muted mb-4">{{ $company->name }}</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        @include('components.company.stat-card', ['label' => 'المركبات', 'value' => $stats['vehicles'], 'icon' => 'bi-car-front'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.company.stat-card', ['label' => 'رصيد المحفظة', 'value' => $stats['wallet_balance'], 'icon' => 'bi-wallet2', 'format' => 'money'])
    </div>
    <div class="col-6 col-xl-3">
        @include('components.company.stat-card', ['label' => 'العمليات', 'value' => $stats['operations'], 'icon' => 'bi-wrench'])
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="stat-value" style="font-size:1.1rem">
                {{ $stats['subscription']?->package?->name ?? 'لا يوجد اشتراك' }}
            </div>
            <div class="stat-label">الاشتراك الحالي</div>
            @if ($stats['subscription'])
                <div class="stat-change up">
                    متبقي {{ max(0, now()->diffInDays($stats['subscription']->ends_at, false)) }} يوم
                </div>
            @endif
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <strong>آخر العمليات</strong>
        <a href="{{ route('company.operations.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
    </div>
    <div class="panel-body">
        @forelse ($recentOperations as $op)
            <div class="ops-item">
                <div class="ops-icon"><i class="bi bi-wrench"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold">{{ number_format((float) $op->amount, 0) }} ر.س</div>
                    <div class="small text-muted text-truncate">
                        {{ $op->vehicle?->plate_number ?? '—' }} · {{ $op->service?->name_ar ?? 'خدمة' }} · {{ $op->center?->name ?? '—' }}
                    </div>
                </div>
                <div class="ops-meta">
                    @include('components.company.status-badge', ['status' => $op->status])
                    <div class="small text-muted mt-1">{{ $op->created_at?->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            @include('components.company.empty-state', ['message' => 'لا توجد عمليات حديثة'])
        @endforelse
    </div>
</div>
@endsection
