@extends('layouts.admin')

@section('title', 'الشركات')
@section('breadcrumb', 'الشركات')
@section('search_action', route('admin.companies.index'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">الشركات</div>
    @can('companies.manage')
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i>إضافة شركة</a>
    @endcan
</div>

<form class="filters-bar row g-2" method="GET">
    <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Enums\CompanyStatus::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>السقف</th>
                    <th>نمط الدفع</th>
                    <th>المركبات</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td class="fw-bold">{{ $company->name }}</td>
                        <td class="dir-ltr">{{ $company->phone }}</td>
                        <td class="dir-ltr">{{ number_format((float) $company->usage_cap, 0) }}</td>
                        <td>{{ $company->payment_mode_label }}</td>
                        <td>{{ $company->vehicles_count }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $company->status])</td>
                        <td class="col-actions">
                            <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                            @can('companies.manage')
                                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('components.admin.empty-state', ['message' => 'لا توجد شركات'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($companies->hasPages())
        <div class="panel-body">{{ $companies->links() }}</div>
    @endif
</div>
@endsection
