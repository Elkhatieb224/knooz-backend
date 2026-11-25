@php
    $panel = $panel ?? 'admin';
    $readRoute = $panel.'.notifications.read';
    $readAllRoute = $panel.'.notifications.read-all';
    $openRoute = $panel.'.notifications.open';
    $destroyRoute = $panel.'.notifications.destroy';
    $indexRoute = $panel.'.notifications.index';
    $layout = 'layouts.'.$panel;
@endphp
@extends($layout)
@section('title', 'الإشعارات')
@section('breadcrumb', 'الإشعارات')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="page-title mb-0">الإشعارات</div>
    <div class="d-flex flex-wrap gap-2">
        @if (($panel ?? '') === 'admin')
            @can('notifications.manage')
                <a href="{{ route('admin.notifications.broadcast') }}" class="btn btn-primary">إرسال إشعار جماعي</a>
            @endcan
        @endif
        <form method="POST" action="{{ route($readAllRoute) }}">
            @csrf
            <button class="btn btn-outline-knooz" type="submit">تعليم الكل كمقروء</button>
        </form>
    </div>
</div>

<form method="GET" action="{{ route($indexRoute) }}" class="filters-bar mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">بحث</label>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="عنوان أو نص...">
        </div>
        <div class="col-md-3">
            <label class="form-label">النوع</label>
            <select name="type" class="form-select">
                <option value="">الكل</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">الحالة</label>
            <select name="read" class="form-select">
                <option value="">الكل</option>
                <option value="unread" @selected(($filters['read'] ?? '') === 'unread')>غير مقروء</option>
                <option value="read" @selected(($filters['read'] ?? '') === 'read')>مقروء</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-knooz w-100" type="submit">تصفية</button>
        </div>
    </div>
</form>

<div class="panel">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            @php
                $isUnread = $notification->read_at === null;
            @endphp
            <div class="list-group-item notification-row {{ $isUnread ? 'is-unread' : '' }}">
                <a href="{{ route($openRoute, $notification->id) }}" class="notification-row-link text-decoration-none">
                    <div class="notification-icon"><i class="bi {{ $notification->icon() }}"></i></div>
                    <div class="notification-copy">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="fw-bold text-body">{{ $notification->title() }}</div>
                            <span class="badge text-bg-light border">{{ $notification->typeLabel() }}</span>
                            @if ($isUnread)
                                <span class="badge bg-danger">جديد</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-1">{{ $notification->body() }}</div>
                        <div class="text-muted small mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                    </div>
                </a>
                <div class="notification-actions">
                    @if ($isUnread)
                        <form method="POST" action="{{ route($readRoute, $notification->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">مقروء</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route($destroyRoute, $notification->id) }}" data-confirm="حذف هذا الإشعار؟">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-3">
                @include('components.'.$panel.'.empty-state', ['message' => 'لا توجد إشعارات', 'icon' => 'bi-bell'])
            </div>
        @endforelse
    </div>
    @if ($notifications->hasPages())
        <div class="panel-body">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
