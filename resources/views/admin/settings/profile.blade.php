@extends('layouts.admin')
@section('title', 'الملف الشخصي')
@section('breadcrumb', 'الملف الشخصي')
@section('content')
<div class="page-title">الملف الشخصي</div>
<div class="panel" style="max-width:720px">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.settings.profile.update') }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-6">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">الهاتف</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">البريد</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">الثيم</label>
                <select name="theme" class="form-select" required>
                    @foreach ($themes as $value => $label)
                        <option value="{{ $value }}" @selected(old('theme', $user->theme?->value)===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">كلمة مرور جديدة</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>
@endsection
