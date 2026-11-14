@php $offer = $offer ?? null; @endphp
<div class="col-md-8">
    <label class="form-label">العنوان</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $offer?->title) }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">نسبة الخصم</label>
    <input type="number" step="0.01" name="discount_percent" class="form-control" value="{{ old('discount_percent', $offer?->discount_percent) }}" required>
</div>
<div class="col-12">
    <label class="form-label">الوصف</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $offer?->description) }}</textarea>
</div>
<div class="col-md-4">
    <label class="form-label">الخدمة</label>
    <select name="service_id" class="form-select">
        <option value="">—</option>
        @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected(old('service_id', $offer?->service_id)==$service->id)>{{ $service->name_ar }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">من</label>
    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', optional($offer?->starts_at)->format('Y-m-d\TH:i')) }}" required>
</div>
<div class="col-md-3">
    <label class="form-label">إلى</label>
    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', optional($offer?->ends_at)->format('Y-m-d\TH:i')) }}" required>
</div>
<div class="col-md-2 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $offer?->is_active ?? true))>
        <label class="form-check-label" for="is_active">نشط</label>
    </div>
</div>
