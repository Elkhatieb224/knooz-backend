@props([
    'label',
    'value',
    'change' => 0,
    'icon' => 'bi-graph-up',
    'format' => 'number',
])

@php
    $display = $format === 'money'
        ? number_format((float) $value, 0).' ر.س'
        : number_format((float) $value);
    $isUp = (float) $change >= 0;
@endphp

<div class="stat-card">
    <div class="stat-icon"><i class="bi {{ $icon }}"></i></div>
    <div class="stat-value">{{ $display }}</div>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-change {{ $isUp ? 'up' : 'down' }}">
        <i class="bi {{ $isUp ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>
        {{ number_format(abs((float) $change), 1) }}%
    </div>
</div>
