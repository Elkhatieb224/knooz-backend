@php
    $panel = $panel ?? 'admin';
    $previewUrl = route($panel.'.notifications.preview');
    $countUrl = route($panel.'.notifications.unread-count');
    $indexUrl = route($panel.'.notifications.index');
    $unread = $unread ?? 0;
@endphp
<div class="dropdown notification-dropdown" data-notification-dropdown data-preview-url="{{ $previewUrl }}" data-count-url="{{ $countUrl }}">
    <button class="icon-btn position-relative" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="الإشعارات" data-notification-toggle>
        <i class="bi bi-bell"></i>
        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger notification-badge {{ $unread > 0 ? '' : 'd-none' }}" data-notification-badge style="font-size:.65rem">{{ $unread > 99 ? '99+' : $unread }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu p-0 shadow">
        <div class="notification-menu-head">
            <strong>الإشعارات</strong>
            <button type="button" class="btn btn-sm btn-link text-decoration-none" data-notification-mark-all>تعليم الكل كمقروء</button>
        </div>
        <div class="notification-menu-body" data-notification-list>
            <div class="notification-state text-muted small p-3" data-notification-loading>جاري التحميل...</div>
        </div>
        <div class="notification-menu-foot">
            <a href="{{ $indexUrl }}">عرض جميع الإشعارات</a>
        </div>
    </div>
</div>
