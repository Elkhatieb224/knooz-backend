@extends('layouts.admin')

@section('title', 'تعديل مستخدم')
@section('breadcrumb', 'تعديل مستخدم')

@section('content')
<div class="page-title">تعديل: {{ $user->name }}</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])
            <div class="col-12">
                <button class="btn btn-primary">تحديث</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>
@endsection
