@extends('layouts.admin')
@section('title', 'إضافة شركة')
@section('breadcrumb', 'إضافة شركة')
@section('content')
<div class="page-title">إضافة شركة</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.companies.store') }}" class="row g-3">
    @csrf
    @include('admin.companies._form')
    <div class="col-12">
        <button class="btn btn-primary">حفظ</button>
        <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
</div></div>
@endsection
