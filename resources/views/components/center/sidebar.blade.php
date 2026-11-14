@php
    $user = auth('center')->user();
    $items = [
        ['route' => 'center.dashboard', 'label' => 'لوحة الرئيسية', 'icon' => 'bi-grid-1x2'],
        ['route' => 'center.bookings.index', 'label' => 'الحجوزات', 'icon' => 'bi-calendar-check'],
        ['route' => 'center.operations.index', 'label' => 'العمليات', 'icon' => 'bi-wrench'],
        ['route' => 'center.reports.index', 'label' => 'التقارير', 'icon' => 'bi-bar-chart'],
        ['route' => 'center.notifications.index', 'label' => 'الإشعارات', 'icon' => 'bi-bell'],
        ['route' => 'center.profile.edit', 'label' => 'الملف الشخصي', 'icon' => 'bi-person'],
    ];
@endphp

<aside class="admin-sidebar" id="centerSidebar" dir="rtl">
    <div class="brand">
        <span class="brand-mark">K</span>
        <div class="brand-text">
            <strong>KNOOZ</strong>
            <small>لوحة المركز</small>
        </div>
    </div>

    <div class="sidebar-section-label">القائمة الرئيسية</div>

    <nav class="sidebar-nav">
        @foreach ($items as $item)
            @php
                $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs(str_replace('.edit', '.*', $item['route']));
            @endphp
            <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="avatar">{{ mb_substr($user->name, 0, 1) }}</div>
        <div class="min-w-0">
            <div class="fw-bold text-truncate">{{ $user->name }}</div>
            <div class="text-muted small">{{ $user->center?->name ?? $user->role_label }}</div>
        </div>
    </div>
</aside>
