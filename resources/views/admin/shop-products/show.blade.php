@extends('layouts.admin')
@section('title', $product->name_ar)
@section('breadcrumb', 'تفاصيل منتج')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">{{ $product->name_ar }}</div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.shop-products.edit', $product) }}" class="btn btn-primary">تعديل</a>
        <form method="POST" action="{{ route('admin.shop-products.toggle', $product) }}">
            @csrf
            <button class="btn btn-outline-warning">{{ $product->is_active ? 'إيقاف' : 'تفعيل' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.shop-products.destroy', $product) }}" data-confirm="حذف المنتج؟">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger">حذف</button>
        </form>
    </div>
</div>
<div class="panel"><div class="panel-body">
    <div class="mb-2"><strong>الاسم الإنجليزي:</strong> {{ $product->name_en ?: '—' }}</div>
    <div class="mb-2"><strong>التصنيف:</strong> {{ \App\Enums\ShopProductCategory::tryLabel($product->category) }}</div>
    <div class="mb-2"><strong>الماركة:</strong> {{ $product->brand ?: '—' }}</div>
    <div class="mb-2"><strong>المقاس:</strong> <span class="dir-ltr">{{ $product->size ?: '—' }}</span></div>
    <div class="mb-2"><strong>السعر:</strong> <span class="dir-ltr">{{ number_format((float) $product->price, 2) }}</span></div>
    <div class="mb-2"><strong>المخزون:</strong> <span class="dir-ltr">{{ $product->stock }}</span></div>
    <div class="mb-2"><strong>المعرّف:</strong> <span class="dir-ltr">{{ $product->slug }}</span></div>
    <div class="mb-2"><strong>الوصف:</strong> {{ $product->description ?: '—' }}</div>
    <div class="mb-2"><strong>الصورة:</strong> {{ $product->image_path ?: '—' }}</div>
    <div class="mb-2"><strong>الحالة:</strong> @include('components.admin.status-badge', ['status' => $product->is_active ? 'active' : 'inactive', 'label' => $product->is_active ? 'نشط' : 'متوقف'])</div>
    @if (!empty($product->meta['notes']))
        <div class="mt-3">
            <strong>بيانات إضافية:</strong>
            <div class="border rounded p-3 mt-2 shop-product-notes">{!! $product->meta['notes'] !!}</div>
        </div>
    @endif

</div></div>
@endsection
