@props(['value', 'unit' => null, 'decimals' => 2])

@php
    $number = rtrim(rtrim(number_format((float) $value, $decimals, ',', ''), '0'), ',');
@endphp
<span {{ $attributes->class(['tabular-nums']) }}>
    {{ $number }}@if ($unit)<span class="ml-0.5 text-base-content/50">{{ $unit }}</span>@endif
</span>
