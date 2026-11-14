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
            <div class="panel-header"><strong>الخدمات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>السعر</th><th>الحالة</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td>{{ $service->name_ar }}</td>
                            <td>{{ number_format((float)$service->base_price, 2) }}</td>
                            <td>@include('components.admin.status-badge', ['status' => $service->is_active ? 'active' : 'inactive', 'label' => $service->is_active ? 'مفعّلة' : 'متوقفة'])</td>
                            <td>
                                <form method="POST" action="{{ route('admin.settings.services.toggle', $service) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning">تبديل</button>
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
                                <td>
                                    <form method="POST" action="{{ route('admin.settings.packages.destroy', $package) }}" data-confirm="حذف الباقة؟" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">حذف</button>
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
