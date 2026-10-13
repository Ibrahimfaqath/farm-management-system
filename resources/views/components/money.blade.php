@props(['amount', 'tone' => false])

@php
    $formatted = 'Rp'.number_format((float) $amount, 0, ',', '.');
    $cls = $tone ? match ($tone) {
        'positive' => 'text-tone-success',
        'negative' => 'text-tone-error',
        default => '',
    } : '';
@endphp
<span {{ $attributes->class(['tabular-nums', $cls]) }}>{{ $formatted }}</span>
