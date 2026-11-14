@extends('layouts.admin')
@section('title', 'تعديل شركة')
@section('breadcrumb', 'تعديل شركة')
@section('content')
<div class="page-title">تعديل: {{ $company->name }}</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.companies.update', $company) }}" class="row g-3">
    @csrf @method('PUT')
    @include('admin.companies._form')
    <div class="col-12">
        <button class="btn btn-primary">تحديث</button>
        <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
</div></div>
@endsection
