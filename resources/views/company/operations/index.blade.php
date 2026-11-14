@extends('layouts.company')

@section('title', 'العمليات')
@section('breadcrumb', 'العمليات')
@section('search_action', route('company.operations.index'))

@section('content')
<div class="page-title">العمليات</div>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>المركبة</th>
                        <th>الخدمة</th>
                        <th>المركز</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($operations as $op)
                        <tr>
                            <td>{{ $op->vehicle?->plate_number ?? '—' }}</td>
                            <td>{{ $op->service?->name_ar ?? '—' }}</td>
                            <td>{{ $op->center?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $op->amount, 0) }} ر.س</td>
                            <td>@include('components.company.status-badge', ['status' => $op->status])</td>
                            <td>{{ $op->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">@include('components.company.empty-state', ['message' => 'لا توجد عمليات'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($operations->hasPages())
        <div class="panel-body border-top">{{ $operations->links() }}</div>
    @endif
</div>
@endsection
