@extends('layouts.admin')

@section('title', $user->name)
@section('breadcrumb', 'تفاصيل المستخدم')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="page-title mb-1">{{ $user->name }}</div>
        <div class="text-muted">{{ $user->email }} · {{ $user->phone }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('users.activate')
            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                @csrf
                <button class="btn btn-outline-warning">{{ $user->status?->value === 'active' ? 'إيقاف' : 'تفعيل' }}</button>
            </form>
        @endcan
        @can('users.manage')
            <form method="POST" action="{{ route('admin.users.regenerate-codes', $user) }}">
                @csrf
                <button class="btn btn-outline-primary">توليد QR/NFC</button>
            </form>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">تعديل</a>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="حذف المستخدم؟">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">حذف</button>
            </form>
        @endcan
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><strong>البيانات</strong></div>
            <div class="panel-body">
                <div class="mb-2">الحالة: @include('components.admin.status-badge', ['status' => $user->status])</div>
                <div class="mb-2">QR: <code>{{ $user->qr_code ?: '—' }}</code></div>
                <div class="mb-2">NFC: <code>{{ $user->nfc_code ?: '—' }}</code></div>
                <div class="mb-2">المحفظة: {{ number_format((float) ($user->wallet?->balance ?? 0), 2) }} ر.س</div>
                <div>التسجيل: {{ $user->created_at?->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header"><strong>المركبات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>اللوحة</th><th>النوع</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($user->vehicles as $vehicle)
                            <tr>
                                <td><a href="{{ route('admin.vehicles.show', $vehicle) }}">{{ $vehicle->plate_number }}</a></td>
                                <td>{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $vehicle->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">@include('components.admin.empty-state', ['message' => 'لا توجد مركبات'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-header"><strong>الاشتراكات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الباقة</th><th>الحالة</th><th>حتى</th></tr></thead>
                    <tbody>
                        @forelse ($user->subscriptions as $sub)
                            <tr>
                                <td>{{ $sub->package?->name }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $sub->status])</td>
                                <td>{{ $sub->ends_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">@include('components.admin.empty-state', ['message' => 'لا توجد اشتراكات'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><strong>العمليات</strong></div>
            <div class="table-responsive">
                <table class="table table-admin">
                    <thead><tr><th>الخدمة</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @forelse ($user->operations as $op)
                            <tr>
                                <td>{{ $op->service?->name_ar }}</td>
                                <td>{{ number_format((float) $op->amount, 2) }}</td>
                                <td>@include('components.admin.status-badge', ['status' => $op->status])</td>
                                <td>{{ $op->created_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد عمليات'])</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
