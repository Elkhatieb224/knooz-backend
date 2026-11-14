@extends('layouts.admin')
@section('title', 'التقارير')
@section('breadcrumb', 'التقارير والتحليلات')
@section('content')
<div class="page-title">التقارير والتحليلات</div>

<form class="filters-bar row g-2 mb-3" method="GET">
    <div class="col-md-3">
        <select name="type" class="form-select">
            @can('reports.financial')
                <option value="financial" @selected($type==='financial')>مالية</option>
            @endcan
            @can('reports.operations')
                <option value="centers" @selected($type==='centers')>أداء المراكز</option>
                <option value="customers" @selected($type==='customers')>استخدام العملاء</option>
            @endcan
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control"></div>
    <div class="col-md-3"><input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تطبيق</button></div>
</form>

@if ($type === 'financial')
    @can('reports.financial')
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">إجمالي الإيرادات</div><div class="stat-value">{{ number_format($financial['total'], 2) }} ر.س</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">عدد المدفوعات</div><div class="stat-value">{{ $financial['count'] }}</div></div></div>
    </div>
    <div class="panel">
        <div class="panel-header"><strong>حسب طريقة الدفع</strong></div>
        <div class="table-responsive">
            <table class="table table-admin">
                <thead><tr><th>الطريقة</th><th>العدد</th><th>الإجمالي</th></tr></thead>
                <tbody>
                @forelse ($financial['by_method'] as $row)
                    <tr>
                        <td>{{ $row->method instanceof \App\Enums\PaymentMethod ? $row->method->label() : (\App\Enums\PaymentMethod::tryFrom($row->method)?->label() ?? $row->method) }}</td>
                        <td>{{ $row->count }}</td>
                        <td>{{ number_format((float)$row->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">@include('components.admin.empty-state', ['message' => 'لا توجد بيانات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endcan
@elseif ($type === 'centers')
    <div class="panel">
        <div class="panel-header"><strong>أداء المراكز</strong></div>
        <div class="table-responsive">
            <table class="table table-admin">
                <thead><tr><th>المركز</th><th>العمليات</th><th>الحجوزات</th><th>الإيراد</th></tr></thead>
                <tbody>
                @forelse ($centers as $center)
                    <tr>
                        <td>{{ $center->name }}</td>
                        <td>{{ $center->operations_count }}</td>
                        <td>{{ $center->bookings_count }}</td>
                        <td>{{ number_format((float)$center->revenue, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد بيانات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="panel mb-3">
        <div class="panel-header"><strong>استخدام العملاء</strong></div>
        <div class="table-responsive">
            <table class="table table-admin">
                <thead><tr><th>العميل</th><th>العمليات</th><th>الإنفاق</th></tr></thead>
                <tbody>
                @forelse ($customerUsage as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->operations_count }}</td>
                        <td>{{ number_format((float)$customer->spent, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">@include('components.admin.empty-state', ['message' => 'لا توجد بيانات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><strong>أعلى الخدمات</strong></div>
        <div class="table-responsive">
            <table class="table table-admin">
                <thead><tr><th>الخدمة</th><th>العدد</th><th>الإيراد</th></tr></thead>
                <tbody>
                @forelse ($topServices as $row)
                    <tr>
                        <td>{{ $row->service?->name_ar }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ number_format((float)$row->revenue, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">@include('components.admin.empty-state', ['message' => 'لا توجد بيانات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
