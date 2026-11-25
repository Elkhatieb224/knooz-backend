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
                <div class="mb-2">
                    <div class="small text-muted mb-1">عمولة منتجات المركز (عامة)</div>
                    <div class="mb-2">
                        {{ \App\Support\CenterServiceCommission::formatPivot($center->product_app_profit !== null ? (float) $center->product_app_profit : null, $center->product_app_profit_mode) }}
                        <span class="small text-muted">— تُطبَّق إن لم تُحدد عمولة مخصصة للمنتج</span>
                    </div>
                    <div class="small text-muted mb-1">قنوات التنفيذ</div>
                    <div class="d-flex flex-wrap gap-1">
                        @if ($center->offers_center_service)<span class="badge text-bg-success">خدمة في المركز</span>@endif
                        @if ($center->offers_home_service)<span class="badge text-bg-primary">خدمة عند المنزل</span>@endif
                        @if ($center->offers_emergency_service)<span class="badge text-bg-warning text-dark">طوارئ الطريق</span>@endif
                        @if (! $center->offers_center_service && ! $center->offers_home_service && ! $center->offers_emergency_service)
                            <span class="text-muted">لا توجد قنوات مفعّلة</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="panel mt-3">
            <div class="panel-header"><strong>الموقع</strong></div>
            <div class="panel-body">
                @include('components.admin.location-map-view', [
                    'lat' => $center->lat,
                    'lng' => $center->lng,
                    'address' => $center->address,
                    'label' => collect([$center->name, $center->address, $center->city])->filter()->unique()->implode(' — '),
                ])
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><strong>الخدمات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>القناة</th><th>سعر الاتفاق</th><th>عمولة التطبيق</th><th>متاحة</th></tr></thead>
                    <tbody>
                    @forelse ($center->services as $service)
                        <tr>
                            <td>{{ $service->name_ar }}</td>
                            <td>{{ \App\Support\ServiceChannels::labels()[$service->channel] ?? ($service->channel ?: '—') }}</td>
                            <td>{{ number_format((float)$service->pivot->contract_price, 2) }} ر.س</td>
                            <td>{{ \App\Support\CenterServiceCommission::formatPivot($service->pivot->app_profit !== null ? (float) $service->pivot->app_profit : null, $service->pivot->app_profit_mode) }}</td>
                            <td>{{ $service->pivot->is_available ? 'نعم' : 'لا' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('components.admin.empty-state', ['message' => 'لا توجد خدمات'])</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
