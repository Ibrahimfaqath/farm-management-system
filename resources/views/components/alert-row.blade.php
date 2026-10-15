@php
    $tone = in_array($tone, ['info', 'success', 'warning', 'error'], true) ? $tone : 'info';
    $icon = match ($tone) {
        'error' => 'error',
        'warning' => 'warning',
        'success' => 'success',
        default => 'info',
    };
@endphp

@props(['tone' => 'info', 'title', 'description' => null, 'url' => null, 'urlLabel' => null])

{{--
    Alert rows carry their tone through a tinted border and icon rather than
    tinted body text: the soft DaisyUI warning text is unreadable on the light
    background, and users read the wording, not the colour.

    The description is clamped to two lines. Without it the rows in the
    dashboard's three-column grid came out at wildly different heights — one ran
    to four lines beside a neighbour at two — so the grid left ragged gaps and
    the list read as broken rather than as ten separate facts. The clamp also
    keeps each row a predictable height on a phone, where ten of them
    stacked would otherwise push everything else a full screen down.

    Nothing is lost: every row links to the record it is about.
--}}
<div {{ $attributes->class([
        'alert items-start gap-2.5 px-3 py-2.5 text-sm',
        'border-'.$tone.'/35 bg-'.$tone.'/10 text-base-content',
    ]) }}
     @if ($tone === 'error') role="alert" @endif>
    <x-icon :name="$icon" :size="16" class="mt-0.5 shrink-0 text-tone-'.$tone.'" />

    {{--
        The title and the link share one line, and the description sits under
        both. Centring the link in the row instead left it floating beside
        whichever line happened to be the middle one, which on a two-line title
        read as a caption pointing at nothing in particular.
    --}}
    <div class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-2">
            <p class="min-w-0 font-medium leading-snug">{{ $title }}</p>

            @if ($url)
                <a href="{{ $url }}"
                   class="shrink-0 rounded-field px-1.5 py-0.5 text-xs font-medium text-{{ $tone }} no-underline transition-colors hover:bg-base-content/5">
                    {{ $urlLabel ?? __('View') }}
                </a>
            @endif
        </div>

        @if ($description)
            <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-base-content/70">{{ $description }}</p>
        @endif
    </div>
</div>