@php
    $user = auth('center')->user();
    $center = $user?->center;
    $offersCenter = (bool) ($center?->offers_center_service ?? true);
    $offersHome = (bool) ($center?->offers_home_service ?? true);
    $offersEmergency = (bool) ($center?->offers_emergency_service ?? true);
    $items = [
        ['route' => 'center.dashboard', 'label' => 'لوحة الرئيسية', 'icon' => 'bi-grid-1x2'],
        ['route' => 'center.bookings.index', 'label' => 'الحجوزات', 'icon' => 'bi-calendar-check', 'needs' => 'bookings'],
        ['route' => 'center.operations.index', 'label' => 'العمليات', 'icon' => 'bi-wrench', 'needs' => 'operations'],
        ['route' => 'center.products.index', 'label' => 'المنتجات', 'icon' => 'bi-box-seam', 'needs' => 'products'],
        ['route' => 'center.reports.index', 'label' => 'التقارير', 'icon' => 'bi-bar-chart'],
        ['route' => 'center.staff.index', 'label' => 'الموظفون', 'icon' => 'bi-people', 'manager_only' => true],
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

    @if ($center)
        <div class="px-3 pb-2">
            <div class="d-flex flex-wrap gap-1">
                @if ($offersCenter)<span class="badge text-bg-light border">مركز</span>@endif
                @if ($offersHome)<span class="badge text-bg-light border">منزل</span>@endif
                @if ($offersEmergency)<span class="badge text-bg-light border">طوارئ</span>@endif
            </div>
        </div>
    @endif

    <nav class="sidebar-nav">
        @foreach ($items as $item)
            @php
                $managerOnly = !empty($item['manager_only']);
                $canSee = ! $managerOnly || ($user->role?->value === 'manager');
                $needs = $item['needs'] ?? null;
                if ($needs === 'bookings' && ! ($offersCenter || $offersHome)) {
                    $canSee = false;
                }
                if ($needs === 'operations' && ! $offersCenter) {
                    $canSee = false;
                }
                if ($needs === 'products' && ! $offersCenter) {
                    $canSee = false;
                }
                $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs(str_replace('.edit', '.*', $item['route']));
            @endphp
            @if ($canSee)
            <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
            @endif
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
