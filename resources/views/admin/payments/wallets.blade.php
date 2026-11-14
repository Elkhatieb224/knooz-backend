@extends('layouts.admin')
@section('title', 'المحافظ')
@section('breadcrumb', 'المحافظ')
@section('search_action', route('admin.wallets.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">المحافظ</div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-knooz">المدفوعات</a>
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-6"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>المالك</th><th>الرصيد</th><th>الحركات</th><th></th></tr></thead>
            <tbody>
            @forelse ($wallets as $wallet)
                <tr>
                    <td>{{ $wallet->user?->name ?? $wallet->company?->name ?? '—' }}</td>
                    <td class="fw-bold">{{ number_format((float)$wallet->balance, 2) }} ر.س</td>
                    <td>{{ $wallet->transactions_count }}</td>
                    <td><a href="{{ route('admin.wallets.show', $wallet) }}" class="btn btn-sm btn-outline-secondary">عرض</a></td>
                </tr>
            @empty
                <tr><td colspan="4">@include('components.admin.empty-state', ['message' => 'لا توجد محافظ'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($wallets->hasPages())<div class="panel-body">{{ $wallets->links() }}</div>@endif
</div>
@endsection
