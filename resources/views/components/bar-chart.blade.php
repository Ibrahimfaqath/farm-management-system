@props(['series' => [], 'label' => null, 'height' => 140, 'valueFormatter' => null])

@php
    // A plain CSS bar chart rather than a charting library: it needs no runtime,
    // adapts to both themes through semantic tokens, and stays readable at
    // dashboard size. The series is filled with zeroes for missing days so the
    // axis never lies about a gap.
    $format = $valueFormatter ?? fn (float $value) => rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');
    $max = max(0.0001, max(array_map(fn (array $point) => (float) $point['value'], $series)));
@endphp

@if ($series !== [])
    <div class="px-4 py-3">
        {{--
            The clip is what keeps this chart off the page's horizontal scroll.

            DaisyUI hides a tooltip with opacity rather than removing it, so the
            absolutely positioned tip is laid out at all times. Seven days across
            a 320px screen leaves each bar 32px wide, while the tip on it needs
            66px — and it hung 17px past the chart on both sides, which is what
            pushed the whole dashboard 7px sideways on a narrow phone.

            `overflow-clip-margin` is the part that matters: clipping stops the
            tip contributing to the scroll width, and the margin still lets it
            paint outside the box, so the tooltip is not cut off when it does
            appear. A plain `overflow: hidden` would have fixed the scroll and
            broken the hover.
        --}}
        <div class="flex h-56 items-end gap-1.5 overflow-clip" style="overflow-clip-margin: 24px"
             role="img"
             aria-label="{{ $label ? $label.': '.implode(', ', array_map(fn (array $p) => $p['label'].' '.$format((float) $p['value']), $series)) : __('Chart') }}">
            @foreach ($series as $point)
                <div class="tooltip tooltip-top flex h-full min-w-0 flex-1 items-end"
                     data-tip="{{ $point['label'] }} · {{ $format((float) $point['value']) }}">
                    <div class="w-full rounded-t bg-primary/70 transition-colors hover:bg-primary"
                         style="height: {{ max(2, round((float) $point['value'] / $max * 100, 1)) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex gap-1.5">
            @foreach ($series as $point)
                <span class="text-meta min-w-0 flex-1 truncate text-center tabular-nums text-base-content/45">{{ $point['label'] }}</span>
            @endforeach
        </div>
    </div>
@endif