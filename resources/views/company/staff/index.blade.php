@extends('layouts.company')

@section('title', 'الموظفون')
@section('breadcrumb', 'الموظفون')
@section('search_action', route('company.staff.index'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">الموظفون</div>
    <a href="{{ route('company.staff.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة موظف</a>
</div>

<form class="row g-2 mb-3" method="GET" action="{{ route('company.staff.index') }}">
    <div class="col-md-4">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث بالاسم / الهاتف / البريد">
    </div>
    <div class="col-md-3">
        <select name="role" class="form-select">
            <option value="">كل الأدوار</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100">تصفية</button>
    </div>
</form>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>البريد</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>آخر دخول</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $me = auth('company')->user(); @endphp
                    @forelse ($staff as $member)
                        <tr>
                            <td class="fw-bold">{{ $member->name }} @if ($member->is($me)) <span class="text-muted small">(أنت)</span> @endif</td>
                            <td>{{ $member->phone ?: '—' }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->role_label }}</td>
                            <td>@include('components.company.status-badge', ['status' => $member->status])</td>
                            <td>{{ $member->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-nowrap">
                                @if ($me->isOwner() || ! $member->isOwner())
                                    <a href="{{ route('company.staff.edit', $member) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                    @unless ($member->is($me))
                                        <form method="POST" action="{{ route('company.staff.toggle-status', $member) }}" class="d-inline"
                                              onsubmit="return confirm('{{ $member->status === \App\Enums\UserStatus::Active ? 'تعطيل هذا الموظف؟' : 'تفعيل هذا الموظف؟' }}')">
                                            @csrf
                                            <button class="btn btn-sm {{ $member->status === \App\Enums\UserStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                {{ $member->status === \App\Enums\UserStatus::Active ? 'تعطيل' : 'تفعيل' }}
                                            </button>
                                        </form>
                                    @endunless
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">@include('components.company.empty-state', ['message' => 'لا يوجد موظفون'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($staff->hasPages())
        <div class="panel-body border-top">{{ $staff->links() }}</div>
    @endif
</div>
@endsection
