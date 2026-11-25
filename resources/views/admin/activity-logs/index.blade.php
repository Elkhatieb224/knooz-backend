@extends('layouts.admin')
@section('title', 'سجل النشاط')
@section('breadcrumb', 'سجل النشاط الإداري')
@section('content')
<div class="page-title">سجل النشاط الإداري</div>
<form class="filters-bar row g-2 mb-3" method="GET">
    <div class="col-md-8"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث في الملخص أو الإجراء"></div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">بحث</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المسؤول</th>
                    <th>الإجراء</th>
                    <th>الملخص</th>
                    <th>الوقت</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="dir-ltr">{{ $log->id }}</td>
                        <td>{{ $log->admin?->name ?: '—' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->summary }}</td>
                        <td class="dir-ltr">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="dir-ltr">{{ $log->ip ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد سجلات بعد'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $logs->links() }}
@endsection
