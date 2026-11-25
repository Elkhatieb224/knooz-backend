@extends('layouts.admin')
@section('title', 'إضافة منتج')
@section('breadcrumb', 'إضافة منتج متجر')
@section('content')
<div class="page-title mb-3">إضافة منتج متجر</div>
<form method="POST" action="{{ route('admin.shop-products.store') }}" class="row g-3" enctype="multipart/form-data">
    @csrf
    @include('admin.shop-products._form')
    <div class="col-12">
        <button class="btn btn-primary">حفظ</button>
        <a href="{{ route('admin.shop-products.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
@endsection
