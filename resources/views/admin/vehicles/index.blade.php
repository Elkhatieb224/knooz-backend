@extends('layouts.admin')
@section('title', 'المركبات')
@section('breadcrumb', 'المركبات')
@section('search_action', route('admin.vehicles.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">المركبات</div>
    @can('vehicles.manage')
        <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">إضافة مركبة</a>
    @endcan
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Enums\VehicleStatus::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>اللوحة</th><th>النوع</th><th>المالك</th><th>QR</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($vehicles as $vehicle)
                <tr>
                    <td class="fw-bold dir-ltr">{{ $vehicle->plate_number }}</td>
                    <td>{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                    <td>{{ $vehicle->user?->name ?? $vehicle->company?->name ?? '—' }}</td>
                    <td><code class="dir-ltr">{{ $vehicle->qr_code }}</code></td>
                    <td>@include('components.admin.status-badge', ['status' => $vehicle->status])</td>
                    <td class="col-actions">
                        <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                        @can('vehicles.manage')
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد مركبات'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($vehicles->hasPages())<div class="panel-body">{{ $vehicles->links() }}</div>@endif
</div>
@endsection
