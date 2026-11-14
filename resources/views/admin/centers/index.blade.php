@extends('layouts.admin')
@section('title', 'مراكز الخدمة')
@section('breadcrumb', 'مراكز الخدمة')
@section('search_action', route('admin.centers.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">مراكز الخدمة</div>
    @can('centers.manage')
        <a href="{{ route('admin.centers.create') }}" class="btn btn-primary">إضافة مركز</a>
    @endcan
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">كل الأنواع</option>
            @foreach (\App\Enums\CenterType::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Enums\CenterStatus::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>الاسم</th><th>النوع</th><th>المدينة</th><th>المسؤول</th><th>الخدمات</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($centers as $center)
                <tr>
                    <td class="fw-bold">{{ $center->name }}</td>
                    <td>{{ $center->type_label }}</td>
                    <td>{{ $center->city }}</td>
                    <td>{{ $center->managerCenterUser?->name ?: ($center->manager?->name ?: '—') }}</td>
                    <td>{{ $center->services_count }}</td>
                    <td>@include('components.admin.status-badge', ['status' => $center->status])</td>
                    <td class="col-actions">
                        <a href="{{ route('admin.centers.show', $center) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                        @can('centers.manage')
                            <a href="{{ route('admin.centers.edit', $center) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">@include('components.admin.empty-state', ['message' => 'لا توجد مراكز'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($centers->hasPages())<div class="panel-body">{{ $centers->links() }}</div>@endif
</div>
@endsection
