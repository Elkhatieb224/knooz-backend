@php $user = $user ?? null; @endphp
<div class="col-md-6">
    <label class="form-label">الاسم</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $user?->name) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user?->phone) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">البريد</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $user?->email) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        @foreach (\App\Enums\UserStatus::labels() as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $user?->status?->value) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">كلمة المرور {{ $user ? '(اختياري)' : '' }}</label>
    <input type="password" name="password" class="form-control" {{ $user ? '' : 'required' }}>
</div>
<div class="col-md-6">
    <label class="form-label">تأكيد كلمة المرور</label>
    <input type="password" name="password_confirmation" class="form-control" {{ $user ? '' : 'required' }}>
</div>
