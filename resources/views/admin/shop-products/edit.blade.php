@extends('layouts.admin')
@section('title', 'تعديل منتج')
@section('breadcrumb', 'تعديل منتج متجر')
@section('content')
<div class="page-title mb-3">تعديل: {{ $product->name_ar }}</div>
<form method="POST" action="{{ route('admin.shop-products.update', $product) }}" class="row g-3">
    @csrf
    @method('PUT')
    @include('admin.shop-products._form')
    <div class="col-12">
        <button class="btn btn-primary">تحديث</button>
        <a href="{{ route('admin.shop-products.show', $product) }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
@endsection
