@php
    $user = auth('company')->user();
    $items = [
        ['route' => 'company.dashboard', 'label' => 'لوحة الرئيسية', 'icon' => 'bi-grid-1x2', 'can' => 'dashboard.view'],
        ['route' => 'company.vehicles.index', 'label' => 'المركبات', 'icon' => 'bi-car-front', 'can' => 'vehicles.view'],
        ['route' => 'company.operations.index', 'label' => 'العمليات', 'icon' => 'bi-wrench', 'can' => 'operations.view'],
        ['route' => 'company.invoices.index', 'label' => 'الفواتير', 'icon' => 'bi-receipt', 'can' => 'invoices.view'],
        ['route' => 'company.subscription.index', 'label' => 'الاشتراك', 'icon' => 'bi-box-seam', 'can' => 'subscription.view'],
        ['route' => 'company.staff.index', 'label' => 'الموظفون', 'icon' => 'bi-people', 'can' => 'staff.manage'],
        ['route' => 'company.notifications.index', 'label' => 'الإشعارات', 'icon' => 'bi-bell'],
        ['route' => 'company.profile.edit', 'label' => 'الملف الشخصي', 'icon' => 'bi-person', 'can' => 'profile.edit'],
    ];
    $items = collect($items)
        ->filter(fn ($item) => empty($item['can']) || $user->can($item['can']))
        ->values()
        ->all();
@endphp

<aside class="admin-sidebar" id="companySidebar" dir="rtl">
    <div class="brand">
        <span class="brand-mark">K</span>
        <div class="brand-text">
            <strong>KNOOZ</strong>
            <small>لوحة الشركة</small>
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
            <div class="text-muted small">{{ $user->role_label }}@if ($user->company?->name) · {{ $user->company->name }}@endif</div>
        </div>
    </div>
</aside>
