@extends('layouts.admin')
@section('title', 'إضافة أدمن')
@section('breadcrumb', 'إضافة أدمن')
@section('content')
<div class="page-title">إضافة حساب إدارة</div>
<div class="panel" style="max-width:640px"><div class="panel-body">
<form method="POST" action="{{ route('admin.roles.store') }}" class="row g-3">
    @csrf
    <div class="col-md-6">
        <label class="form-label">الاسم</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">البريد</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">الهاتف</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">الدور</label>
        <select name="role" class="form-select" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">كلمة المرور</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">تأكيد كلمة المرور</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', 'active')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">إنشاء</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
</div></div>
@endsection
