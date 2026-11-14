@extends('layouts.admin')
@section('title', 'الطلبات والشكاوي')
@section('breadcrumb', 'الطلبات والشكاوي')
@section('search_action', route('admin.requests.index', ['tab' => $tab]))

@section('content')
<div class="page-title">الطلبات والشكاوي</div>

<ul class="nav nav-pills mb-3 gap-2">
    @foreach ([
        'bookings' => 'الحجوزات',
        'operations' => 'العمليات',
        'complaints' => 'الشكاوى',
        'emergency' => 'طلبات الطوارئ',
    ] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               style="{{ $tab === $key ? 'background:var(--primary)' : 'color:var(--text-muted)' }}"
               href="{{ route('admin.requests.index', ['tab' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<form class="filters-bar row g-2" method="GET">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @php
                $statusMap = match($tab) {
                    'operations' => $operationStatuses,
                    'complaints' => $complaintStatuses,
                    'emergency' => $emergencyStatuses,
                    default => $bookingStatuses,
                };
            @endphp
            @foreach ($statusMap as $value => $label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>

<div class="panel">
    <div class="table-responsive">
        @if ($tab === 'bookings')
            <table class="table table-admin">
                <thead><tr><th>العميل</th><th>المركبة</th><th>المركز</th><th>الخدمة</th><th>الموعد</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->user?->name }}</td>
                        <td class="dir-ltr">{{ $item->vehicle?->plate_number }}</td>
                        <td>{{ $item->center?->name }}</td>
                        <td>{{ $item->service?->name_ar }}</td>
                        <td class="dir-ltr">{{ $item->scheduled_at?->format('Y-m-d H:i') }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $item->status])</td>
                        <td class="col-actions">
                            @can('bookings.manage')
                            <form method="POST" action="{{ route('admin.requests.bookings.update', $item) }}" class="d-flex gap-1 justify-content-end">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($bookingStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status?->value===$value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">حفظ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('components.admin.empty-state', ['message' => 'لا توجد حجوزات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        @elseif ($tab === 'operations')
            <table class="table table-admin">
                <thead><tr><th>العميل</th><th>المركبة</th><th>الخدمة</th><th>المبلغ</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->user?->name }}</td>
                        <td>{{ $item->vehicle?->plate_number }}</td>
                        <td>{{ $item->service?->name_ar }}</td>
                        <td>{{ number_format((float)$item->amount, 2) }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $item->status])</td>
                        <td>
                            @can('bookings.manage')
                            <form method="POST" action="{{ route('admin.requests.operations.update', $item) }}" class="d-flex gap-1">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($operationStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status?->value===$value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">حفظ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد عمليات'])</td></tr>
                @endforelse
                </tbody>
            </table>
        @elseif ($tab === 'complaints')
            <table class="table table-admin">
                <thead><tr><th>العميل</th><th>الموضوع</th><th>المسند</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->user?->name }}</td>
                        <td>
                            <div class="fw-bold">{{ $item->subject }}</div>
                            <div class="small text-muted text-truncate" style="max-width:280px">{{ $item->body }}</div>
                        </td>
                        <td>{{ $item->assignee?->name ?: '—' }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $item->status])</td>
                        <td>
                            @can('complaints.manage')
                            <form method="POST" action="{{ route('admin.requests.complaints.update', $item) }}" class="d-flex flex-column gap-1">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($complaintStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status?->value===$value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="ملاحظة" value="{{ $item->admin_notes }}">
                                <button class="btn btn-sm btn-primary">حفظ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">@include('components.admin.empty-state', ['message' => 'لا توجد شكاوى'])</td></tr>
                @endforelse
                </tbody>
            </table>
        @else
            <table class="table table-admin">
                <thead><tr><th>العميل</th><th>المركبة</th><th>العنوان</th><th>الموظف</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->user?->name }}</td>
                        <td>{{ $item->vehicle?->plate_number }}</td>
                        <td>{{ $item->address }}</td>
                        <td>{{ $item->employee?->name ?: '—' }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $item->status])</td>
                        <td>
                            @can('bookings.manage')
                            <form method="POST" action="{{ route('admin.requests.emergency.update', $item) }}" class="d-flex gap-1">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm">
                                    @foreach ($emergencyStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($item->status?->value===$value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-primary">حفظ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد طلبات طوارئ'])</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </div>
    @if ($items->hasPages())
        <div class="panel-body">{{ $items->links() }}</div>
    @endif
</div>
@endsection
