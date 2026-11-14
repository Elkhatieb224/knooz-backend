@extends('layouts.admin')
@section('title', 'تعديل مركبة')
@section('breadcrumb', 'تعديل مركبة')
@section('content')
<div class="page-title">تعديل: {{ $vehicle->plate_number }}</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" class="row g-3">
    @csrf @method('PUT')
    @include('admin.vehicles._form')
    <div class="col-12">
        <button class="btn btn-primary">تحديث</button>
        <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">رجوع</a>
    </div>
</form>
</div></div>
@endsection
