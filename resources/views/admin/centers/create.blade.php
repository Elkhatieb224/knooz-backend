@extends('layouts.admin')
@section('title', 'إضافة مركز')
@section('breadcrumb', 'إضافة مركز')
@section('content')
<div class="page-title mb-3">إضافة مركز خدمة</div>
<div class="panel" style="height:auto">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.centers.store') }}" class="row g-3 align-items-start">
            @csrf
            @include('admin.centers._form')
            <div class="col-12">
                <button class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.centers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
