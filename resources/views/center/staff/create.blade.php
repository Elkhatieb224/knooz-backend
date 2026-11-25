@extends('layouts.center')
@section('title', 'إضافة موظف')
@section('breadcrumb', 'إضافة موظف')
@section('content')
<div class="page-title">إضافة موظف مركز</div>
<div class="panel" style="max-width:640px">
    <div class="panel-body">
        <form method="POST" action="{{ route('center.staff.store') }}" class="row g-3">
            @csrf
            <div class="col-12"><label class="form-label">الاسم</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="col-md-6"><label class="form-label">البريد</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
            <div class="col-md-6"><label class="form-label">الهاتف</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
            <div class="col-md-6"><label class="form-label">كلمة المرور</label><input type="password" name="password" class="form-control" required></div>
            <div class="col-md-6">
                <label class="form-label">الدور</label>
                <select name="role" class="form-select" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">حفظ</button>
                <a href="{{ route('center.staff.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
