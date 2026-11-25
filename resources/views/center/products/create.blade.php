@extends('layouts.center')
@section('title', 'إضافة منتج')
@section('breadcrumb', 'إضافة منتج')
@section('content')
<div class="page-title mb-3">إضافة منتج للمركز</div>
<div class="panel" style="max-width:860px;height:auto">
    <div class="panel-body">
        <p class="small text-muted mb-3">حدّد سعر البيع والمخزون. عمولة التطبيق تضبطها الإدارة (عامة للمركز أو مخصصة للمنتج) ويُعرض صافي المركز تلقائياً.</p>
        <form method="POST" action="{{ route('center.products.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @include('center.products._form')
            <div class="col-12">
                <button class="btn btn-primary">حفظ</button>
                <a href="{{ route('center.products.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
