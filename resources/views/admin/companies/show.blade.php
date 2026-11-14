@extends('layouts.admin')
@section('title', $company->name)
@section('breadcrumb', 'تفاصيل الشركة')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="page-title mb-1">{{ $company->name }}</div>
        <div class="text-muted">{{ $company->payment_mode_label }} · سقف {{ number_format((float)$company->usage_cap,0) }}</div>
    </div>
    @can('companies.manage')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-primary">تعديل</a>
        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" data-confirm="حذف الشركة؟">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">حذف</button>
        </form>
    </div>
    @endcan
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><strong>البيانات</strong></div>
            <div class="panel-body">
                <div class="mb-2">الحالة: @include('components.admin.status-badge', ['status' => $company->status])</div>
                <div class="mb-2">الهاتف: {{ $company->phone ?: '—' }}</div>
                <div class="mb-2">البريد: {{ $company->email ?: '—' }}</div>
                <div class="mb-2">السجل: {{ $company->commercial_register ?: '—' }}</div>
                <div>المحفظة: {{ number_format((float)($company->wallet?->balance ?? 0), 2) }} ر.س</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header d-flex justify-content-between align-items-center">
                <strong>المركبات</strong>
                <a href="{{ route('admin.vehicles.create', ['company_id' => $company->id]) }}" class="btn btn-sm btn-outline-primary">إضافة مركبة</a>
            </div>
            @can('companies.manage')
            <div class="panel-body border-bottom">
                <form method="POST" action="{{ route('admin.companies.attach-vehicle', $company) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">ربط مركبة موجودة</label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">اختر مركبة غير مربوطة بشركة...</option>
                            @foreach ($availableVehicles as $available)
                                <option value="{{ $available->id }}">{{ $available->plate_number }} — {{ $available->brand }} {{ $available->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100">ربط بالمركبة</button>
                    </div>
                </form>
            </div>
            @endcan
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>اللوحة</th><th>النوع</th><th>QR</th><th>NFC</th></tr></thead>
                    <tbody>
                        @forelse ($company->vehicles as $vehicle)
                            <tr>
                                <td><a href="{{ route('admin.vehicles.show', $vehicle) }}">{{ $vehicle->plate_number }}</a></td>
                                <td>{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                                <td><code>{{ $vehicle->qr_code }}</code></td>
                                <td><code>{{ $vehicle->nfc_code }}</code></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد مركبات'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-header"><strong>مستخدمو لوحة الشركة</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الهاتف</th>
                            <th>الدور</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($company->companyUsers as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->phone ?: '—' }}</td>
                                <td>{{ $member->role_label }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $member->status])</td>
                                <td>{{ $member->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا يوجد مستخدمون'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><strong>الفواتير</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الرقم</th><th>الفترة</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($company->invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->number }}</td>
                                <td>{{ $invoice->period_start?->format('Y-m-d') }} → {{ $invoice->period_end?->format('Y-m-d') }}</td>
                                <td>{{ number_format((float)$invoice->total, 2) }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $invoice->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد فواتير'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
