@props(['amount', 'tone' => false])

@php
    $formatted = 'Rp'.number_format((float) $amount, 0, ',', '.');
    $cls = $tone ? match ($tone) {
        'positive' => 'text-success',
        'negative' => 'text-error',
        default => '',
    } : '';
@endphp
<span {{ $attributes->class(['tabular-nums', $cls]) }}>{{ $formatted }}</span>
