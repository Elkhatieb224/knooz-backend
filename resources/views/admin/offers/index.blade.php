@extends('layouts.admin')
@section('title', 'العروض')
@section('breadcrumb', 'العروض والإعلانات')
@section('search_action', route('admin.offers.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">العروض والإعلانات</div>
    <a href="{{ route('admin.offers.create') }}" class="btn btn-primary">إضافة عرض</a>
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث"></div>
    <div class="col-md-3">
        <select name="active" class="form-select">
            <option value="">الكل</option>
            <option value="1" @selected(request('active')==='1')>نشط</option>
            <option value="0" @selected(request('active')==='0')>متوقف</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead><tr><th>العنوان</th><th>الخدمة</th><th>الخصم</th><th>الفترة</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($offers as $offer)
                <tr>
                    <td class="fw-bold">{{ $offer->title }}</td>
                    <td>{{ $offer->service?->name_ar ?: '—' }}</td>
                    <td class="dir-ltr">{{ number_format((float)$offer->discount_percent, 0) }}%</td>
                    <td class="dir-ltr">{{ $offer->starts_at?->format('Y-m-d') }} → {{ $offer->ends_at?->format('Y-m-d') }}</td>
                    <td>@include('components.admin.status-badge', ['status' => $offer->is_active ? 'active' : 'inactive', 'label' => $offer->is_active ? 'نشط' : 'متوقف'])</td>
                    <td class="col-actions">
                        <a href="{{ route('admin.offers.show', $offer) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                        <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">@include('components.admin.empty-state', ['message' => 'لا توجد عروض'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($offers->hasPages())<div class="panel-body">{{ $offers->links() }}</div>@endif
</div>
@endsection
