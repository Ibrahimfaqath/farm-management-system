@props([
    'type' => 'line',
    'series' => [],
    'labels' => [],
    'label' => null,
    'height' => 260,
    'valueFormatter' => null,
    'filled' => true,
    'showLegend' => false,
    'empty' => null,
])

@php
    $normalised = array_values(array_map(fn ($s) => [
        'label' => $s['label'] ?? null,
        'data' => array_values(array_map(fn ($v) => is_numeric($v) ? (float) $v : null, $s['data'] ?? [])),
        'tone' => $s['tone'] ?? null,
    ], $series));

    /*
     * Drawn only when there is something to show.
     *
     * "Something" means at least one non-zero value, not merely a series. A
     * series of zeros draws an axis running 0 to 1 and no bars in it, which
     * reads as a broken chart rather than as an absence of data — and the frame
     * would sit there looking like the page had failed to finish loading.
     */
    $hasData = collect($normalised)
        ->flatMap(fn ($s) => $s['data'])
        ->contains(fn ($v) => $v !== null && $v !== 0.0);

    $payload = json_encode([
        'type' => $type,
        'labels' => $labels,
        'series' => $normalised,
        'filled' => $filled,
        'legend' => $showLegend,
        'format' => $valueFormatter ? 'decimal1' : 'plain',
    ], JSON_THROW_ON_ERROR);
@endphp

{{--
    A chart, drawn by Chart.js on demand.

    Chart.js is imported dynamically by the script, so it is never part of the
    page bundle on a screen without a chart. Importing it statically would ship
    it on the login form, where it does nothing and weighs ~60KB over the wire.

    Only the pieces actually drawn are registered — line and bar controllers,
    the point, line and bar elements, the category and linear scales, the
    tooltip, the legend and the area filler. The rest of Chart.js, including
    every controller and every scale it ships with, is never loaded.

    Colours are not declared here. The script reads them from the theme's own
    custom properties, so a chart is correct in both the light theme and `black`
    without a second set of values, and follows the tokens rather than freezing
    them.

    The canvas carries a text alternative describing the figures, because a
    picture of a chart is not something a screen reader can read.
--}}
@if ($hasData)
    <figure class="chart-figure" style="--chart-height: {{ $height }}px">
        <canvas
            data-chart
            data-chart-payload="{{ $payload }}"
            height="{{ $height }}"
            role="img"
            aria-label="{{ $label ?? __('Chart') }}: {{ implode(', ', array_map(fn ($s) => ($s['label'] ?? '') . ' ' . implode(' ', array_map(fn ($v) => is_numeric($v) ? \App\Support\Number::compact((float) $v, 1) : '—', $s['data'] ?? [])), $series)) }}"
        ></canvas>

        {{-- Painted before the script arrives, replaced once Chart.js loads.
             A chart region that sits blank for a moment reads as a broken
             panel, and the script arrives after the network round trip. --}}
        <noscript>
            <p class="chart-fallback text-sm text-base-content/60">
                {{ implode(' · ', array_map(fn ($s) => ($s['label'] ?? '') . ': ' . implode(', ', array_map(fn ($v) => is_numeric($v) ? \App\Support\Number::compact((float) $v, 1) : '—', $s['data'] ?? [])), $series)) }}
            </p>
        </noscript>
    </figure>
@else
    {{--
        Said in words, in the space the chart would have taken.

        Silence here reads as a page that failed to load; a sentence reads as
        what it is — there is nothing recorded for this period yet.
    --}}
    <div class="flex items-center gap-2 text-sm text-base-content/55"
         style="min-height: {{ $height - 60 }}px">
        <x-icon name="chart" :size="18" class="shrink-0 text-base-content/30" />
        <span>{{ $empty ?? __('Nothing recorded for this period yet.') }}</span>
    </div>
@endif