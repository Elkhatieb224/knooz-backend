@extends('layouts.center')
@section('title', 'موظفو المركز')
@section('breadcrumb', 'الموظفون')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">موظفو المركز</div>
    <a href="{{ route('center.staff.create') }}" class="btn btn-primary">إضافة موظف</a>
</div>
<form class="row g-2 mb-3" method="GET">
    <div class="col-md-8"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead><tr><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>الدور</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
                @forelse ($staff as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->phone ?: '—' }}</td>
                        <td>{{ $member->role_label }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $member->status])</td>
                        <td class="col-actions">
                            <a href="{{ route('center.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                            <form method="POST" action="{{ route('center.staff.toggle', $member) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning">{{ $member->status?->value === 'active' ? 'تعطيل' : 'تفعيل' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا يوجد موظفون'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $staff->links() }}
@endsection
