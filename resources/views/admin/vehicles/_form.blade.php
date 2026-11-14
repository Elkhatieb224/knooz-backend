@php $vehicle = $vehicle ?? null; @endphp
<div class="col-md-4">
    <label class="form-label">رقم اللوحة</label>
    <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number', $vehicle?->plate_number) }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">الماركة</label>
    <input type="text" name="brand" class="form-control" value="{{ old('brand', $vehicle?->brand) }}">
</div>
<div class="col-md-4">
    <label class="form-label">الموديل</label>
    <input type="text" name="model" class="form-control" value="{{ old('model', $vehicle?->model) }}">
</div>
<div class="col-md-3">
    <label class="form-label">اللون</label>
    <input type="text" name="color" class="form-control" value="{{ old('color', $vehicle?->color) }}">
</div>
<div class="col-md-3">
    <label class="form-label">السنة</label>
    <input type="number" name="year" class="form-control" value="{{ old('year', $vehicle?->year) }}">
</div>
<div class="col-md-3">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $vehicle?->status?->value)===$value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">عميل فردي</label>
    <select name="user_id" class="form-select">
        <option value="">—</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected(old('user_id', $vehicle?->user_id)==$u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">شركة</label>
    <select name="company_id" class="form-select">
        <option value="">—</option>
        @foreach ($companies as $c)
            <option value="{{ $c->id }}" @selected(old('company_id', $vehicle?->company_id)==$c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
</div>
