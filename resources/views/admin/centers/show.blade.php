@extends('layouts.admin')
@section('title', $center->name)
@section('breadcrumb', 'تفاصيل المركز')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="page-title mb-1">{{ $center->name }}</div>
        <div class="text-muted">{{ $center->type_label }} · {{ $center->city }}</div>
    </div>
    @can('centers.manage')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.centers.edit', $center) }}" class="btn btn-primary">تعديل</a>
        <form method="POST" action="{{ route('admin.centers.destroy', $center) }}" data-confirm="حذف المركز؟">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">حذف</button>
        </form>
    </div>
    @endcan
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><strong>البيانات</strong></div>
            <div class="panel-body">
                <div class="mb-2">الحالة: @include('components.admin.status-badge', ['status' => $center->status])</div>
                <div class="mb-2">المسؤول: {{ $center->managerCenterUser?->name ?: ($center->manager?->name ?: '—') }}</div>
                <div class="mb-2">الهاتف: {{ $center->phone ?: '—' }}</div>
                <div class="mb-2">العنوان: {{ $center->address ?: '—' }}</div>
                <div>الإحداثيات: {{ $center->lat }}, {{ $center->lng }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><strong>الخدمات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>سعر الاتفاق</th><th>ربح التطبيق</th><th>متاحة</th></tr></thead>
                    <tbody>
                    @forelse ($center->services as $service)
                        <tr>
                            <td>{{ $service->name_ar }}</td>
                            <td>{{ number_format((float)$service->pivot->contract_price, 2) }}</td>
                            <td>{{ number_format((float)$service->pivot->app_profit, 2) }}</td>
                            <td>{{ $service->pivot->is_available ? 'نعم' : 'لا' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد خدمات'])</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
