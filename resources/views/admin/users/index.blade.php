@extends('layouts.admin')

@section('title', 'المستخدمون الأفراد')
@section('breadcrumb', 'المستخدمون الأفراد')
@section('search_action', route('admin.users.index'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">المستخدمون الأفراد</div>
    @can('users.manage')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i>إضافة مستخدم</a>
    @endcan
</div>

<form class="filters-bar row g-2" method="GET">
    <div class="col-md-5">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث بالاسم / الهاتف / البريد">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach (\App\Enums\UserStatus::labels() as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-knooz w-100">تصفية</button>
    </div>
</form>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>الحالة</th>
                    <th>المركبات</th>
                    <th>التسجيل</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="fw-bold">{{ $user->name }}</td>
                        <td class="dir-ltr">{{ $user->phone }}</td>
                        <td class="dir-ltr">{{ $user->email }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $user->status])</td>
                        <td>{{ $user->vehicles_count }}</td>
                        <td class="dir-ltr">{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td class="col-actions">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                            @can('users.manage')
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('components.admin.empty-state', ['message' => 'لا يوجد مستخدمون'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="panel-body">{{ $users->links() }}</div>
    @endif
</div>
@endsection
