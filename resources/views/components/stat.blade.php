@props(['label', 'value', 'hint' => null, 'tone' => 'neutral', 'icon' => null])

@php
    // Coloured numbers use the text-safe `tone-*` tokens: DaisyUI's warning
    // yellow is unreadable as text on the light background.
    $tones = [
        'neutral' => 'text-base-content',
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'success' => 'text-tone-success',
        'warning' => 'text-tone-warning',
        'error' => 'text-tone-error',
        'info' => 'text-tone-info',
    ];
    $tone = $tones[$tone] ?? $tones['neutral'];
@endphp

{{--
    A single metric tile.

    DaisyUI's `stats` wrapper is deliberately not used here: it forces
    `grid-auto-flow: column` and `overflow-x: auto` on every child, which
    breaks a responsive grid of independently bordered tiles.

    The padding steps down on a phone, and that step is the point. Six tiles at
    the desktop rhythm took 550px of a 375px screen — so on a livestock list the
    reader scrolled past the title, the button and six tiles before reaching the
    first row of data, which is the thing they came for. Nothing is removed at
    the smaller size: label, value and hint all remain, stacked in three lines
    that read the same way they do on a desktop.
--}}
<div {{ $attributes->class(['card border border-base-300 bg-base-100 px-3 py-2.5 transition-shadow duration-150 hover:shadow-md lg:px-4 lg:py-3']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-label stat-title line-clamp-2 font-medium text-base-content/60">
            {{ $label }}
        </p>
        @if ($icon)
            {{-- The icon is decoration on top of a number that already carries
                 the meaning, so it is dropped below the desktop breakpoint
                 rather than spending a whole tap-target width on it. --}}
            <span class="hidden shrink-0 text-base-content/30 lg:block [&_svg]:size-4">{!! $icon !!}</span>
        @endif
    </div>

    <p class="stat-value mt-1 truncate text-lg font-semibold tabular-nums lg:text-xl {{ $tone }}">{{ $value }}</p>

    @if (filled($hint))
        {{-- The hint wraps rather than truncating. At tile width on a phone a
             single line was not enough for "Below reorder point", and the
             ellipsis cut it to "below the reorde…" — a tile whose supporting
             line is unreadable is worse than one that is a little taller. Two
             lines is the cap. --}}
        <p class="text-meta stat-desc mt-0.5 line-clamp-2 leading-snug text-base-content/50">{{ $hint }}</p>
    @endif
</div>