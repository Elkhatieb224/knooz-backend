@php
    $user = auth('admin')->user();
    $theme = $user?->theme?->value ?? 'light';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Language" content="ar">
    <title>@yield('title', 'لوحة التحكم') — KNOOZ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}?v=2" rel="stylesheet">
    <link href="{{ asset('css/notifications.css') }}?v=1" rel="stylesheet">
    @stack('styles')
    <script>
        (function () {
            try {
                var t = localStorage.getItem('knooz-theme');
                if (t) document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
</head>
<body class="admin-body">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-shell">
        <div class="admin-main">
            @include('components.admin.header')
            <main class="admin-content">
                @include('components.admin.alert')
                @yield('content')
            </main>
        </div>
        @include('components.admin.sidebar')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}?v=2"></script>
    <script src="{{ asset('js/notifications.js') }}?v=1"></script>
    @stack('scripts')
</body>
</html>
