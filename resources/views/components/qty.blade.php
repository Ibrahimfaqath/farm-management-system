@props(['value', 'unit' => null, 'decimals' => null])

@php
    /*
     * A quantity, written the way the interface writes every other number.
     *
     * The previous version passed `','` and `''` as separators, which the
     * process locale then overrode: the inventory stock read "2,400" while the
     * money beside it read "Rp16.500", so one row carried two conventions. It
     * also padded whole numbers to two decimals and trimmed them back off.
     *
     * Whole quantities print without a decimal — 2.400, not 2.400,00 — and a
     * fractional one keeps enough digits to be meaningful.
     */
    $number = $decimals === null
        ? \App\Support\Number::compact($value)
        : \App\Support\Number::format($value, (int) $decimals);
@endphp
<span {{ $attributes->class(['tabular-nums']) }}>
    {{ $number }}@if ($unit)<span class="ml-1 text-base-content/50">{{ $unit }}</span>@endif
</span>