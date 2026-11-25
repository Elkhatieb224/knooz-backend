@extends('layouts.admin')
@section('title', 'إرسال إشعار جماعي')
@section('breadcrumb', 'إشعار جماعي')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">إرسال إشعار جماعي</div>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>
<div class="panel" style="max-width:720px">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.notifications.broadcast.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">الفئة المستهدفة</label>
                <select name="audience" class="form-select" required>
                    @foreach ($audiences as $value => $label)
                        <option value="{{ $value }}" @selected(old('audience') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required maxlength="180">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">نص الإشعار</label>
                <textarea name="body" rows="5" class="form-control @error('body') is-invalid @enderror" required maxlength="2000">{{ old('body') }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" data-confirm-click="إرسال الإشعار للفئة المحددة؟">إرسال</button>
        </form>
    </div>
</div>
@endsection
