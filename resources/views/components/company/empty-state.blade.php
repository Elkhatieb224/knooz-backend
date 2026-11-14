@props(['message' => 'لا توجد بيانات للعرض', 'icon' => 'bi-inbox'])

<div class="empty-state">
    <i class="bi {{ $icon }}"></i>
    <div>{{ $message }}</div>
    @isset($slot)
        {{ $slot }}
    @endisset
</div>
