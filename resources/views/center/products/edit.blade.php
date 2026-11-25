@extends('layouts.center')
@section('title', 'تعديل منتج')
@section('breadcrumb', 'تعديل منتج')
@section('content')
<div class="page-title mb-3">تعديل منتج</div>
<div class="panel" style="max-width:860px;height:auto">
    <div class="panel-body">
        <form method="POST" action="{{ route('center.products.update', $product) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')
            @include('center.products._form', ['product' => $product])
            <div class="col-12">
                <button class="btn btn-primary">حفظ التعديلات</button>
                <a href="{{ route('center.products.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
