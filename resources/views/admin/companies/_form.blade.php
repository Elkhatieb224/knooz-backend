@php $company = $company ?? null; @endphp
<div class="col-md-6">
    <label class="form-label">اسم الشركة</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $company?->name) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">السجل التجاري</label>
    <input type="text" name="commercial_register" class="form-control" value="{{ old('commercial_register', $company?->commercial_register) }}">
</div>
<div class="col-md-6">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $company?->phone) }}">
</div>
<div class="col-md-6">
    <label class="form-label">البريد</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $company?->email) }}">
</div>
<div class="col-md-4">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $company?->status?->value) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">نمط الدفع</label>
    <select name="payment_mode" class="form-select" required>
        @foreach ($paymentModes as $value => $label)
            <option value="{{ $value }}" @selected(old('payment_mode', $company?->payment_mode?->value) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">سقف الاستخدام</label>
    <input type="number" step="0.01" name="usage_cap" class="form-control" value="{{ old('usage_cap', $company?->usage_cap) }}">
</div>
<div class="col-md-4">
    <label class="form-label">الحد الائتماني</label>
    <input type="number" step="0.01" name="credit_limit" class="form-control" value="{{ old('credit_limit', $company?->credit_limit) }}">
</div>
<div class="col-12">
    <label class="form-label">ملاحظات</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $company?->notes) }}</textarea>
</div>
