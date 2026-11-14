@extends('layouts.admin')
@section('title', $offer->title)
@section('breadcrumb', 'تفاصيل عرض')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">{{ $offer->title }}</div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-primary">تعديل</a>
        <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" data-confirm="حذف العرض؟">@csrf @method('DELETE')<button class="btn btn-outline-danger">حذف</button></form>
    </div>
</div>
<div class="panel"><div class="panel-body">
    <div class="mb-2">{{ $offer->description }}</div>
    <div class="mb-2">الخدمة: {{ $offer->service?->name_ar ?: '—' }}</div>
    <div class="mb-2">الخصم: {{ number_format((float)$offer->discount_percent, 0) }}%</div>
    <div class="mb-2">الفترة: {{ $offer->starts_at }} → {{ $offer->ends_at }}</div>
    <div>الحالة: @include('components.admin.status-badge', ['status' => $offer->is_active ? 'active' : 'inactive', 'label' => $offer->is_active ? 'نشط' : 'متوقف'])</div>
</div></div>
@endsection
