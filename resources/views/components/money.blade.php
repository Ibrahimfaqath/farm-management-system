@props(['amount', 'tone' => false])

@php
    $full = 'Rp'.\App\Support\Number::format((float) $amount, 0);
    $compact = \App\Support\Number::compactMoney($amount);
    $cls = $tone ? match ($tone) {
        'positive' => 'text-tone-success',
        'negative' => 'text-tone-error',
        default => '',
    } : '';
@endphp

{{--
    The full figure is the source of truth; the short one is only what a phone
    shows. A desktop reader sees the same nine-digit number as before. A phone
    reader gets "Rp8,9 jt", while the exact value stays in the title, the
    accessible name and the document source for tests and assistive technology.
--}}
<span {{ $attributes->class(['tabular-nums', 'whitespace-nowrap', $cls]) }} title="{{ $full }}">
    @if ($compact !== $full)
        <span class="sr-only">{{ $full }}</span>
        <span aria-hidden="true">
            <span class="sm:hidden">{{ $compact }}</span>
            <span class="hidden sm:inline">{{ $full }}</span>
        </span>
    @else
        {{ $full }}
    @endif
</span>
