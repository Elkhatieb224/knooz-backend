@extends('layouts.admin')
@section('title', 'الصلاحيات والأدوار')
@section('breadcrumb', 'الصلاحيات والأدوار')
@section('search_action', route('admin.roles.index'))
@section('content')
<div class="page-title">الصلاحيات والأدوار</div>

<div class="panel mb-4">
    <div class="panel-header"><strong>حسابات الإدارة</strong></div>
    <form class="filters-bar row g-2 m-3" method="GET">
        <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
        <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($admins as $admin)
                <tr>
                    <td>{{ $admin->name }}</td>
                    <td class="dir-ltr">{{ $admin->email }}</td>
                    <td>{{ $admin->role_label }}</td>
                    <td>@include('components.admin.status-badge', ['status' => $admin->status])</td>
                    <td class="col-actions"><a href="{{ route('admin.roles.edit', $admin) }}" class="btn btn-sm btn-outline-primary">تعديل</a></td>
                </tr>
            @empty
                <tr><td colspan="5">@include('components.admin.empty-state', ['message' => 'لا يوجد أدمن'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($admins->hasPages())<div class="panel-body">{{ $admins->links() }}</div>@endif
</div>

<div class="panel">
    <div class="panel-header"><strong>مصفوفة الصلاحيات</strong></div>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th>الصلاحية</th>
                    @foreach ($roleLabels as $role => $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach ($matrix as $permission => $roles)
                <tr>
                    <td>{{ $labels[$permission] ?? $permission }}</td>
                    @foreach ($roleLabels as $role => $label)
                        <td class="text-center">
                            @if (in_array($role, $roles, true))
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-dash text-muted"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
