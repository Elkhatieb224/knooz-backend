@extends('layouts.admin')
@section('title', 'الإعدادات')
@section('breadcrumb', 'الإعدادات')
@section('content')
<div class="page-title">الإعدادات</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><strong>إعدادات عامة</strong></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.settings.general') }}" class="row g-3">
                    @csrf
                    @foreach ([
                        'app_name' => 'اسم التطبيق',
                        'app_name_en' => 'الاسم بالإنجليزية',
                        'support_phone' => 'هاتف الدعم',
                        'support_email' => 'بريد الدعم',
                        'currency' => 'العملة',
                        'vat_percent' => 'ضريبة القيمة المضافة %',
                        'emergency_radius_km' => 'نطاق الطوارئ (كم)',
                    ] as $key => $label)
                        <div class="col-md-6">
                            <label class="form-label">{{ $label }}</label>
                            <input type="text" name="{{ $key }}" class="form-control" value="{{ old($key, $settings[$key] ?? '') }}">
                        </div>
                    @endforeach
                    <div class="col-md-6">
                        <label class="form-label">الثيم الافتراضي</label>
                        <select name="default_theme" class="form-select">
                            <option value="light" @selected(($settings['default_theme'] ?? '') === 'light')>فاتح</option>
                            <option value="dark" @selected(($settings['default_theme'] ?? '') === 'dark')>داكن</option>
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-primary">حفظ الإعدادات</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><strong>الخدمات (الظهور والقناة والمتطلبات)</strong></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.settings.services.store') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-6"><input name="name_ar" class="form-control" placeholder="اسم الخدمة" required></div>
                    <div class="col-md-6"><input name="name_en" class="form-control" placeholder="English name"></div>
                    <div class="col-md-4"><input name="slug" class="form-control" placeholder="slug (اختياري)"></div>
                    <div class="col-md-4"><input name="kind_key" class="form-control" placeholder="kind_key مثل tire/fuel"></div>
                    <div class="col-md-4"><input type="number" step="0.01" name="base_price" class="form-control" placeholder="السعر"></div>
                    <div class="col-md-4">
                        <select name="channel" class="form-select" required>
                            @foreach ($serviceChannels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="flow_hint" class="form-select">
                            <option value="">مسار التطبيق</option>
                            @foreach ($serviceFlows as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><input type="number" name="sort_order" class="form-control" placeholder="ترتيب" value="100"></div>
                    <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="الوصف"></textarea></div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($serviceRequirements as $value => $label)
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="requires[]" value="{{ $value }}">
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">إظهار في التطبيق</label>
                        </div>
                    </div>
                    <div class="col-md-4"><button class="btn btn-primary w-100">إضافة خدمة</button></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>القناة</th><th>المتطلبات</th><th>السعر</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td>
                                <strong>{{ $service->name_ar }}</strong>
                                <div class="text-muted small">{{ $service->kind_key ?: '—' }} · {{ $service->flow_hint ?: '—' }}</div>
                            </td>
                            <td>{{ $serviceChannels[$service->channel] ?? $service->channel }}</td>
                            <td class="small">{{ implode('، ', $service->requiredFields() ?: ['—']) }}</td>
                            <td>{{ number_format((float)$service->base_price, 2) }}</td>
                            <td>@include('components.admin.status-badge', ['status' => $service->is_active ? 'active' : 'inactive', 'label' => $service->is_active ? 'ظاهرة' : 'مخفية'])</td>
                            <td class="text-nowrap">
                                <form method="POST" action="{{ route('admin.settings.services.toggle', $service) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning">{{ $service->is_active ? 'إخفاء' : 'إظهار' }}</button>
                                </form>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-service-{{ $service->id }}">تعديل</button>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-service-{{ $service->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('admin.settings.services.update', $service) }}" class="row g-2 p-2">
                                    @csrf @method('PUT')
                                    <div class="col-md-4"><input name="name_ar" class="form-control" value="{{ $service->name_ar }}" required></div>
                                    <div class="col-md-4"><input name="name_en" class="form-control" value="{{ $service->name_en }}"></div>
                                    <div class="col-md-4"><input name="kind_key" class="form-control" value="{{ $service->kind_key }}" placeholder="kind_key"></div>
                                    <div class="col-md-3">
                                        <select name="channel" class="form-select" required>
                                            @foreach ($serviceChannels as $value => $label)
                                                <option value="{{ $value }}" @selected($service->channel === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="flow_hint" class="form-select">
                                            <option value="">مسار التطبيق</option>
                                            @foreach ($serviceFlows as $value => $label)
                                                <option value="{{ $value }}" @selected($service->flow_hint === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3"><input type="number" step="0.01" name="base_price" class="form-control" value="{{ $service->base_price }}"></div>
                                    <div class="col-md-3"><input type="number" name="sort_order" class="form-control" value="{{ $service->sort_order }}"></div>
                                    <div class="col-12"><textarea name="description" class="form-control" rows="2">{{ $service->description }}</textarea></div>
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach ($serviceRequirements as $value => $label)
                                                <label class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="requires[]" value="{{ $value }}" @checked(in_array($value, $service->requiredFields(), true))>
                                                    <span class="form-check-label">{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($service->is_active)>
                                            <label class="form-check-label">إظهار في التطبيق</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4"><button class="btn btn-success w-100">حفظ التعديل</button></div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="panel">
            <div class="panel-header"><strong>الباقات والاشتراكات</strong></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('admin.settings.packages.store') }}" class="row g-2 mb-4">
                    @csrf
                    <div class="col-md-3"><input name="name" class="form-control" placeholder="اسم الباقة" required></div>
                    <div class="col-md-2">
                        <select name="type" class="form-select" required>
                            @foreach ($packageTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><input type="number" name="duration_days" class="form-control" placeholder="أيام" required></div>
                    <div class="col-md-1"><input type="number" name="operations_count" class="form-control" placeholder="عمليات" required></div>
                    <div class="col-md-1"><input type="number" name="vehicles_count" class="form-control" placeholder="سيارات" required></div>
                    <div class="col-md-1"><input type="number" step="0.01" name="price" class="form-control" placeholder="السعر" required></div>
                    <div class="col-md-1"><input type="number" step="0.01" name="discount_percent" class="form-control" placeholder="خصم%"></div>
                    <div class="col-md-1 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">نشط</label></div></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">إضافة</button></div>
                </form>

                <div class="table-responsive">
                    <table class="table table-admin">
                        <thead><tr><th>الاسم</th><th>النوع</th><th>المدة</th><th>السعر</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($packages as $package)
                            <tr>
                                <td>{{ $package->name }}</td>
                                <td>{{ $package->type_label }}</td>
                                <td>{{ $package->duration_days }} يوم</td>
                                <td>{{ number_format((float)$package->price, 2) }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $package->is_active ? 'active' : 'inactive', 'label' => $package->is_active ? 'نشط' : 'متوقف'])</td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-package-{{ $package->id }}">تعديل</button>
                                    <form method="POST" action="{{ route('admin.settings.packages.destroy', $package) }}" data-confirm="حذف الباقة؟" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-package-{{ $package->id }}">
                                <td colspan="6">
                                    <form method="POST" action="{{ route('admin.settings.packages.update', $package) }}" class="row g-2 p-2">
                                        @csrf @method('PUT')
                                        <div class="col-md-3"><input name="name" class="form-control" value="{{ $package->name }}" required></div>
                                        <div class="col-md-2">
                                            <select name="type" class="form-select" required>
                                                @foreach ($packageTypes as $value => $label)
                                                    <option value="{{ $value }}" @selected($package->type?->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1"><input type="number" name="duration_days" class="form-control" value="{{ $package->duration_days }}" required></div>
                                        <div class="col-md-1"><input type="number" name="operations_count" class="form-control" value="{{ $package->operations_count }}" required></div>
                                        <div class="col-md-1"><input type="number" name="vehicles_count" class="form-control" value="{{ $package->vehicles_count }}" required></div>
                                        <div class="col-md-1"><input type="number" step="0.01" name="price" class="form-control" value="{{ $package->price }}" required></div>
                                        <div class="col-md-1"><input type="number" step="0.01" name="discount_percent" class="form-control" value="{{ $package->discount_percent }}"></div>
                                        <div class="col-md-1 d-flex align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($package->is_active)>
                                                <label class="form-check-label">نشط</label>
                                            </div>
                                        </div>
                                        <div class="col-md-1"><button class="btn btn-success w-100">حفظ</button></div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد باقات'])</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($packages->hasPages())
                    <div class="mt-3">{{ $packages->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
