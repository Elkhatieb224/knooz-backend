@extends('layouts.company')

@section('title', 'الفواتير')
@section('breadcrumb', 'الفواتير')
@section('search_action', route('company.invoices.index'))

@section('content')
<div class="page-title">الفواتير</div>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>الرقم</th>
                        <th>الفترة</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="fw-bold">{{ $invoice->number }}</td>
                            <td>
                                {{ $invoice->period_start?->format('Y-m-d') }}
                                —
                                {{ $invoice->period_end?->format('Y-m-d') }}
                            </td>
                            <td>{{ number_format((float) $invoice->total, 0) }} ر.س</td>
                            <td>@include('components.company.status-badge', ['status' => $invoice->status])</td>
                            <td>
                                <a href="{{ route('company.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">@include('components.company.empty-state', ['message' => 'لا توجد فواتير'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($invoices->hasPages())
        <div class="panel-body border-top">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
