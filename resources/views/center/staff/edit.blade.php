@extends('layouts.center')
@section('title', 'تعديل موظف')
@section('breadcrumb', 'تعديل موظف')
@section('content')
<div class="page-title">تعديل: {{ $staff->name }}</div>
<div class="panel" style="max-width:640px">
    <div class="panel-body">
        <form method="POST" action="{{ route('center.staff.update', $staff) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-12"><label class="form-label">الاسم</label><input name="name" class="form-control" value="{{ old('name', $staff->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">البريد</label><input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}" required></div>
            <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}"></div>
            <div class="col-md-6"><label class="form-label">كلمة مرور جديدة (اختياري)</label><input type="password" name="password" class="form-control"></div>
            <div class="col-md-6">
                <label class="form-label">الدور</label>
                <select name="role" class="form-select" required @disabled($isSelf)>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $staff->role?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($isSelf)<input type="hidden" name="role" value="{{ $staff->role?->value }}"><div class="form-text">لا يمكن تغيير دورك الحالي من هنا.</div>@endif
            </div>
            <div class="col-12">
                <button class="btn btn-primary">تحديث</button>
                <a href="{{ route('center.staff.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>
@endsection
