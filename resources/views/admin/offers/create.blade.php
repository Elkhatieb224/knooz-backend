@extends('layouts.admin')
@section('title', 'إضافة عرض')
@section('breadcrumb', 'إضافة عرض')
@section('content')
<div class="page-title">إضافة عرض</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.offers.store') }}" class="row g-3" enctype="multipart/form-data">
    @csrf
    @include('admin.offers._form')
    <div class="col-12"><button class="btn btn-primary">حفظ</button><a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary">إلغاء</a></div>
</form>
</div></div>
@endsection
