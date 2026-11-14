@php
    $user = auth('center')->user();
    $unread = app(\App\Services\Notifications\NotificationService::class)->unreadCount($user);
@endphp

<header class="admin-header">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="icon-btn d-lg-none" data-sidebar-toggle aria-label="القائمة">
            <i class="bi bi-list"></i>
        </button>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('center.dashboard') }}">KNOOZ</a></li>
                <li class="breadcrumb-item active" aria-current="page">@yield('breadcrumb', 'لوحة الرئيسية')</li>
            </ol>
        </nav>
    </div>

    <div class="header-actions">
        <form action="@yield('search_action', '#')" method="GET" class="d-none d-md-block">
            <input type="search" name="q" value="{{ request('q') }}" class="search-box" placeholder="بحث...">
        </form>

        @include('components.notifications.dropdown', ['panel' => 'center', 'unread' => $unread])

        <button type="button"
                class="icon-btn"
                data-theme-toggle
                data-theme-url="{{ route('center.theme') }}"
                title="تبديل الثيم">
            <i class="bi bi-moon-stars"></i>
        </button>

        <div class="dropdown">
            <button class="user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <span class="dot">{{ mb_substr($user->name, 0, 1) }}</span>
                <span class="d-none d-sm-inline">{{ $user->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('center.profile.edit') }}"><i class="bi bi-person"></i><span>الملف الشخصي</span></a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('center.logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-left"></i><span>تسجيل الخروج</span></button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
