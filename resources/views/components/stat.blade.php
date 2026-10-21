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

    // A stat receives its value as a rendered string, so this is the one place
    // that can recognise a rupiah figure without changing 30 call sites. The
    // short form is applied by the client at phone widths; the server keeps the
    // full string as the accessible name and as the desktop rendering.
    $compactValue = null;

    if (is_string($value) && preg_match('/^Rp(-?[\d.]+)$/', $value, $matches) === 1) {
        $compactValue = \App\Support\Number::compactMoney((float) str_replace('.', '', $matches[1]));

        if ($compactValue === $value) {
            $compactValue = null;
        }
    }
@endphp

{{--
    A single metric tile.

    DaisyUI's `stats` wrapper is deliberately not used here: it forces
    `grid-auto-flow: column` and `overflow-x: auto` on every child, which
    breaks a responsive grid of independently bordered tiles. The `stat-*`
    typography classes are kept for consistency; `card` owns the surface.
--}}
<div {{ $attributes->class(['card border border-base-300 bg-base-100 px-4 py-3']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-label stat-title whitespace-normal font-medium leading-tight text-base-content/65">
            {{ $label }}
        </p>
        @if ($icon)
            <span class="shrink-0 text-base-content/30 [&_svg]:size-4">{!! $icon !!}</span>
        @endif
    </div>
    <p class="stat-value mt-1 truncate text-xl font-semibold tabular-nums {{ $tone }}"
       @if ($compactValue !== null) data-compact-value="{{ $compactValue }}" aria-label="{{ $value }}" title="{{ $value }}" @endif>{{ $value }}</p>
    @if (filled($hint))
        <p class="text-meta stat-desc mt-0.5 whitespace-normal leading-snug text-base-content/50">{{ $hint }}</p>
    @endif
</div>