@extends('layouts.admin')
@section('title', 'تفاصيل محفظة')
@section('breadcrumb', 'تفاصيل محفظة')
@section('content')
<div class="page-title">محفظة {{ $wallet->user?->name ?? $wallet->company?->name }}</div>
<div class="panel mb-3">
    <div class="panel-body">
        الرصيد الحالي: <strong class="fs-4 text-primary">{{ number_format((float)$wallet->balance, 2) }} ر.س</strong>
    </div>
</div>
<div class="panel">
    <div class="panel-header"><strong>الحركات</strong></div>
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>النوع</th><th>المبلغ</th><th>الرصيد بعد</th><th>الوصف</th><th>بواسطة</th><th>التاريخ</th></tr></thead>
            <tbody>
            @forelse ($transactions as $tx)
                <tr>
                    <td>@include('components.admin.status-badge', ['status' => $tx->type, 'label' => $tx->type_label])</td>
                    <td>{{ number_format((float)$tx->amount, 2) }}</td>
                    <td>{{ number_format((float)$tx->balance_after, 2) }}</td>
                    <td>{{ $tx->description }}</td>
                    <td>{{ $tx->creator?->name ?: '—' }}</td>
                    <td>{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد حركات'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($transactions->hasPages())<div class="panel-body">{{ $transactions->links() }}</div>@endif
</div>
@endsection
