@extends('layouts.admin')
@section('title', 'تعديل مركز')
@section('breadcrumb', 'تعديل مركز')
@section('content')
<div class="page-title">تعديل: {{ $center->name }}</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.centers.update', $center) }}" class="row g-3">
    @csrf @method('PUT')
    @include('admin.centers._form')
    <div class="col-12">
        <button class="btn btn-primary">تحديث</button>
        <a href="{{ route('admin.centers.show', $center) }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
</div></div>
@endsection
