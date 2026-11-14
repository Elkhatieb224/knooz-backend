@extends('layouts.admin')
@section('title', 'تعديل دور')
@section('breadcrumb', 'تعديل دور')
@section('content')
<div class="page-title">تعديل: {{ $adminUser->name }}</div>
<div class="panel" style="max-width:560px"><div class="panel-body">
<form method="POST" action="{{ route('admin.roles.update', $adminUser) }}" class="row g-3">
    @csrf @method('PUT')
    <div class="col-12">
        <label class="form-label">الدور</label>
        <select name="role" class="form-select" required>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $adminUser->role?->value)===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $adminUser->status?->value)===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">حفظ</button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
</div></div>
@endsection
