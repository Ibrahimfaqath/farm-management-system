@props(['series' => [], 'label' => null, 'height' => 140, 'valueFormatter' => null])

@php
    // A plain CSS bar chart rather than a charting library: it needs no runtime,
    // adapts to both themes through semantic tokens, and stays readable at
    // dashboard size. The series is filled with zeroes for missing days so the
    // axis never lies about a gap.
    $format = $valueFormatter ?? fn (float $value) => \App\Support\Number::compact($value, 1);
    $max = max(0.0001, max(array_map(fn (array $point) => (float) $point['value'], $series)));
@endphp

@if ($series !== [])
    <div class="px-4 py-3">
        <div class="flex h-56 items-end gap-1.5" role="img"
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
                <span class="text-meta min-w-0 flex-1 truncate text-center text-base-content/45">{{ $point['label'] }}</span>
            @endforeach
        </div>
    </div>
@endif