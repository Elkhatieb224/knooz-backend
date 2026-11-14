@props(['status', 'label' => null])

@php
    $value = is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status;
    $text = $label ?? (is_object($status) && method_exists($status, 'label') ? $status->label() : $value);
    $class = match ($value) {
        'active', 'completed', 'paid', 'approved', 'closed', 'credit' => 'success',
        'inactive', 'cancelled', 'rejected', 'failed', 'suspended', 'debit' => 'danger',
        'pending', 'new', 'open', 'in_progress', 'assigned' => 'warning',
        default => 'muted',
    };
@endphp

<span {{ $attributes->merge(['class' => "status-badge {$class}"]) }}>{{ $text }}</span>
