@php
    $center = $center ?? null;
    $selected = $center?->services?->keyBy('id') ?? collect();
@endphp
<div class="col-md-6">
    <label class="form-label">اسم المركز</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $center?->name) }}" required>
</div>
<div class="col-md-3">
    <label class="form-label">النوع</label>
    <select name="type" class="form-select" required>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $center?->type?->value)===$value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $center?->status?->value)===$value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $center?->phone) }}">
</div>
<div class="col-md-4">
    <label class="form-label">البريد</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $center?->email) }}">
</div>
<div class="col-md-4">
    <label class="form-label">المدينة</label>
    <input type="text" name="city" class="form-control" value="{{ old('city', $center?->city) }}">
</div>
<div class="col-md-8">
    <label class="form-label">العنوان</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $center?->address) }}">
</div>
<div class="col-md-2">
    <label class="form-label">خط العرض</label>
    <input type="text" name="lat" class="form-control" value="{{ old('lat', $center?->lat) }}">
</div>
<div class="col-md-2">
    <label class="form-label">خط الطول</label>
    <input type="text" name="lng" class="form-control" value="{{ old('lng', $center?->lng) }}">
</div>
<div class="col-md-6">
    <label class="form-label">مسؤول المركز</label>
    <select name="manager_center_user_id" class="form-select">
        <option value="">—</option>
        @foreach ($managers as $manager)
            <option value="{{ $manager->id }}" @selected(old('manager_center_user_id', $center?->manager_center_user_id)==$manager->id)>{{ $manager->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label">إظهار الأرباح في تطبيق المركز</label>
    <div class="form-check form-switch mt-2">
        <input type="hidden" name="show_revenue" value="0">
        <input
            type="checkbox"
            class="form-check-input"
            name="show_revenue"
            value="1"
            id="show_revenue"
            @checked((string) old('show_revenue', $center?->show_revenue ?? ($center?->type?->value === 'owned' ? '1' : '0')) === '1')
        >
        <label class="form-check-label" for="show_revenue">
            يسمح بعرض صافي الإيرادات (بعد عمولة التطبيق) للمدير في التطبيق — للمتعاقد غالباً يُلغى
        </label>
    </div>
</div>
<div class="col-12">
    <label class="form-label fw-bold">الخدمات والأسعار</label>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>تفعيل</th><th>الخدمة</th><th>سعر الاتفاق</th><th>ربح التطبيق</th><th>متاحة</th></tr></thead>
            <tbody>
            @foreach ($services as $service)
                @php $pivot = $selected->get($service->id)?->pivot; @endphp
                <tr>
                    <td><input type="checkbox" name="services[{{ $service->id }}][enabled]" value="1" @checked(old("services.{$service->id}.enabled", (bool)$pivot))></td>
                    <td>{{ $service->name_ar }}</td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="services[{{ $service->id }}][contract_price]" value="{{ old("services.{$service->id}.contract_price", $pivot->contract_price ?? $service->base_price) }}"></td>
                    <td><input type="number" step="0.01" class="form-control form-control-sm" name="services[{{ $service->id }}][app_profit]" value="{{ old("services.{$service->id}.app_profit", $pivot->app_profit ?? 0) }}"></td>
                    <td><input type="checkbox" name="services[{{ $service->id }}][is_available]" value="1" @checked(old("services.{$service->id}.is_available", $pivot->is_available ?? true))></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
