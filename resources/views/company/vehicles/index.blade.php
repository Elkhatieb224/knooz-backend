@extends('layouts.company')

@section('title', 'المركبات')
@section('breadcrumb', 'المركبات')
@section('search_action', route('company.vehicles.index'))

@section('content')
<div class="page-title">المركبات</div>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>اللوحة</th>
                        <th>الماركة</th>
                        <th>الموديل</th>
                        <th>السنة</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="fw-bold">{{ $vehicle->plate_number }}</td>
                            <td>{{ $vehicle->brand }}</td>
                            <td>{{ $vehicle->model }}</td>
                            <td>{{ $vehicle->year }}</td>
                            <td>@include('components.company.status-badge', ['status' => $vehicle->status])</td>
                            <td>
                                <a href="{{ route('company.vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">@include('components.company.empty-state', ['message' => 'لا توجد مركبات'])</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($vehicles->hasPages())
        <div class="panel-body border-top">{{ $vehicles->links() }}</div>
    @endif
</div>
@endsection
