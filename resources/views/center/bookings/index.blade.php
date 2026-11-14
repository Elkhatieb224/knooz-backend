@extends('layouts.center')

@section('title', 'الحجوزات')
@section('breadcrumb', 'الحجوزات')
@section('search_action', route('center.bookings.index'))

@section('content')
<div class="page-title">الحجوزات</div>

<div class="panel mb-3">
    <div class="panel-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">بحث</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="عميل أو لوحة">
            </div>
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select name="status" class="form-select">
                    <option value="">الكل</option>
                    @foreach ($bookingStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">تصفية</button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>المركبة</th>
                        <th>الخدمة</th>
                        <th>الموعد</th>
                        <th>الحالة</th>
                        <th>تحديث</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->user?->name ?? '—' }}</td>
                            <td>{{ $booking->vehicle?->plate_number ?? '—' }}</td>
                            <td>{{ $booking->service?->name_ar ?? '—' }}</td>
                            <td>{{ $booking->scheduled_at?->format('Y-m-d H:i') }}</td>
                            <td>@include('components.center.status-badge', ['status' => $booking->status])</td>
                            <td style="min-width:220px">
                                <form method="POST" action="{{ route('center.bookings.update', $booking) }}" class="d-flex gap-1">
                                    @csrf @method('PATCH')
                                    <select name="status" class="form-select form-select-sm">
                                        @foreach ($bookingStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected($booking->status?->value===$value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-primary">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">@include('components.center.empty-state', ['message' => 'لا توجد حجوزات'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($bookings->hasPages())
        <div class="panel-body border-top">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
