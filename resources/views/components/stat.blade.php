@props(['label', 'value', 'hint' => null, 'tone' => 'neutral', 'icon' => null])

@php
    $tones = [
        'neutral' => 'text-base-content',
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'error' => 'text-error',
        'info' => 'text-info',
    ];
    $tone = $tones[$tone] ?? $tones['neutral'];
@endphp

{{--
    A single metric tile.

    DaisyUI's `stats` wrapper is deliberately not used here: it sets
    `grid-auto-flow: column`, `overflow-x: auto` and `white-space: nowrap` on
    every child, which breaks a responsive grid of independently bordered
    tiles. We keep the `stat-*` typography classes for consistency and let
    `card` own the surface.
--}}
<div {{ $attributes->class(['card border border-base-300 bg-base-100 px-4 py-3']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="stat-title truncate text-[11px] font-medium uppercase tracking-wide text-base-content/50">
            {{ $label }}
        </p>
        @if ($icon)
            <span class="shrink-0 text-base-content/30 [&_svg]:size-4">{!! $icon !!}</span>
        @endif
    </div>
    <p class="stat-value mt-1 truncate text-xl font-semibold tabular-nums {{ $tone }}">{{ $value }}</p>
    @if ($hint)
        <p class="stat-desc mt-0.5 truncate text-[11px] text-base-content/50">{{ $hint }}</p>
    @endif
</div>
