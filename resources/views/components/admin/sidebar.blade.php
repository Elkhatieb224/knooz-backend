@php
    $user = auth('admin')->user();
    $items = [
        ['route' => 'admin.dashboard', 'label' => 'لوحة الرئيسية', 'icon' => 'bi-grid-1x2', 'perm' => 'dashboard.view'],
        ['route' => 'admin.users.index', 'label' => 'المستخدمون الأفراد', 'icon' => 'bi-people', 'perm' => 'users.view'],
        ['route' => 'admin.companies.index', 'label' => 'الشركات', 'icon' => 'bi-building', 'perm' => 'companies.view'],
        ['route' => 'admin.centers.index', 'label' => 'مراكز الخدمة', 'icon' => 'bi-geo-alt', 'perm' => 'centers.view'],
        ['route' => 'admin.requests.index', 'label' => 'الطلبات والشكاوي', 'icon' => 'bi-inbox', 'perm' => 'bookings.view'],
        ['route' => 'admin.payments.index', 'label' => 'المحافظ والمدفوعات', 'icon' => 'bi-wallet2', 'perm' => 'payments.view', 'also' => 'admin.wallets.index'],
        ['route' => 'admin.vehicles.index', 'label' => 'المركبات', 'icon' => 'bi-car-front', 'perm' => 'vehicles.view'],
        ['route' => 'admin.reports.index', 'label' => 'التقارير والتحليلات', 'icon' => 'bi-bar-chart', 'perm' => null],
        ['route' => 'admin.offers.index', 'label' => 'العروض والإعلانات', 'icon' => 'bi-megaphone', 'perm' => 'offers.manage'],
        ['route' => 'admin.shop-products.index', 'label' => 'منتجات المتجر', 'icon' => 'bi-bag', 'perm' => 'shop.manage'],
        ['route' => 'admin.content.index', 'label' => 'المحتوى', 'icon' => 'bi-file-earmark-text', 'perm' => 'settings.manage'],
        ['route' => 'admin.settings.index', 'label' => 'الإعدادات', 'icon' => 'bi-gear', 'perm' => 'settings.manage'],
        ['route' => 'admin.activity-logs.index', 'label' => 'سجل النشاط', 'icon' => 'bi-clock-history', 'perm' => 'settings.manage'],
        ['route' => 'admin.roles.index', 'label' => 'الصلاحيات والأدوار', 'icon' => 'bi-shield-lock', 'perm' => 'roles.manage'],
        ['route' => 'admin.notifications.index', 'label' => 'الإشعارات', 'icon' => 'bi-bell', 'perm' => 'notifications.view'],
    ];
@endphp

<aside class="admin-sidebar" id="adminSidebar" dir="rtl">
    <div class="brand">
        <span class="brand-mark">K</span>
        <div class="brand-text">
            <strong>KNOOZ</strong>
            <small>كنوز الطاقة</small>
        </div>
    </div>

    <div class="sidebar-section-label">القائمة الرئيسية</div>

    <nav class="sidebar-nav">
        @foreach ($items as $item)
            @php
                $canSee = $item['perm'] === null
                    ? (auth('admin')->user()->can('reports.financial') || auth('admin')->user()->can('reports.operations'))
                    : auth('admin')->user()->can($item['perm']);
                $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']));
                if (!empty($item['also'])) {
                    $active = $active || request()->routeIs(str_replace('.index', '.*', $item['also']));
                }
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
            <div class="text-muted small">{{ $user->role_label }}</div>
        </div>
    </div>
</aside>
