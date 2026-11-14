@extends('layouts.admin')

@section('title', 'إضافة مستخدم')
@section('breadcrumb', 'إضافة مستخدم')

@section('content')
<div class="page-title">إضافة مستخدم فردي</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
            @csrf
            @include('admin.users._form')
            <div class="col-12">
                <button class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
