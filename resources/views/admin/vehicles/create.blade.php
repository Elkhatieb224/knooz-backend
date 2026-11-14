@extends('layouts.admin')
@section('title', 'إضافة مركبة')
@section('breadcrumb', 'إضافة مركبة')
@section('content')
<div class="page-title">إضافة مركبة</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.vehicles.store') }}" class="row g-3">
    @csrf
    @include('admin.vehicles._form')
    <div class="col-12">
        <button class="btn btn-primary">حفظ</button>
        <a href="{{ route('admin.vehicles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
</div></div>
@endsection
