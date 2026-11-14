@extends('layouts.admin')
@section('title', 'تعديل عرض')
@section('breadcrumb', 'تعديل عرض')
@section('content')
<div class="page-title">تعديل: {{ $offer->title }}</div>
<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('admin.offers.update', $offer) }}" class="row g-3">
    @csrf @method('PUT')
    @include('admin.offers._form')
    <div class="col-12"><button class="btn btn-primary">تحديث</button><a href="{{ route('admin.offers.show', $offer) }}" class="btn btn-outline-secondary">رجوع</a></div>
</form>
</div></div>
@endsection
