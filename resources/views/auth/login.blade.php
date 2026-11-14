@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="guest-card">
    <div class="text-center mb-4">
        <div class="brand-mark mx-auto mb-3" style="width:56px;height:56px;font-size:1.4rem">K</div>
        <h1 class="h3 fw-bold mb-1">KNOOZ</h1>
        <p class="text-muted mb-0">تسجيل دخول لوحات الإدارة / الشركات / المراكز</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني أو الهاتف</label>
            <input type="text" name="login" value="{{ old('login') }}" class="form-control dir-ltr" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label">كلمة المرور</label>
            <input type="password" name="password" class="form-control dir-ltr" required autocomplete="current-password">
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" @checked(old('remember'))>
            <label class="form-check-label" for="remember">تذكرني</label>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">دخول</button>
    </form>
</div>
@endsection
