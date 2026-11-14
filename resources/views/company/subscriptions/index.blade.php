@extends('layouts.company')

@section('title', 'الاشتراك')
@section('breadcrumb', 'الاشتراك')

@section('content')
<div class="page-title">الاشتراك</div>

<div class="panel mb-3">
    <div class="panel-header"><strong>الاشتراك الحالي</strong></div>
    <div class="panel-body">
        @if ($subscription)
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">الباقة</div>
                    <div class="fw-bold">{{ $subscription->package?->name ?? '—' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">الحالة</div>
                    @include('components.company.status-badge', ['status' => $subscription->status])
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">يبدأ</div>
                    <div>{{ $subscription->starts_at?->format('Y-m-d') }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">ينتهي</div>
                    <div>{{ $subscription->ends_at?->format('Y-m-d') }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">العمليات المستخدمة</div>
                    <div>
                        {{ $subscription->operations_used }}
                        /
                        {{ $subscription->package?->operations_count ?? '—' }}
                    </div>
                </div>
            </div>
        @else
            @include('components.company.empty-state', ['message' => 'لا يوجد اشتراك نشط حالياً', 'icon' => 'bi-box-seam'])
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-header"><strong>سجل الاشتراكات</strong></div>
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>الباقة</th>
                        <th>الفترة</th>
                        <th>الاستخدام</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td>{{ $item->package?->name ?? '—' }}</td>
                            <td>{{ $item->starts_at?->format('Y-m-d') }} — {{ $item->ends_at?->format('Y-m-d') }}</td>
                            <td>{{ $item->operations_used }} / {{ $item->package?->operations_count ?? '—' }}</td>
                            <td>@include('components.company.status-badge', ['status' => $item->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">@include('components.company.empty-state', ['message' => 'لا يوجد سجل'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($history->hasPages())
        <div class="panel-body border-top">{{ $history->links() }}</div>
    @endif
</div>
@endsection
