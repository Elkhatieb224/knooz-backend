@extends('layouts.company')

@section('title', 'تعديل موظف')
@section('breadcrumb', 'تعديل موظف')

@section('content')
<div class="page-title">تعديل الموظف: {{ $staff->name }}</div>
<div class="panel" style="max-width:720px">
    <div class="panel-body">
        <form method="POST" action="{{ route('company.staff.update', $staff) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-6">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $staff->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">البريد</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">الدور</label>
                @if ($isSelf)
                    <input type="hidden" name="role" value="{{ $staff->role?->value }}">
                    <input type="text" class="form-control" value="{{ $staff->role_label }}" disabled>
                    <div class="form-text">لا يمكنك تغيير دورك بنفسك.</div>
                @else
                    <select name="role" class="form-select" required>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $staff->role?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">كلمة مرور جديدة (اختياري)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">حفظ</button>
                <a href="{{ route('company.staff.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>
@endsection
