@props(['days'])

@php
    $days = $days === null ? null : (int) $days;
@endphp

<span @class(['text-tone-warning' => $days !== null && $days <= 7])>
    {{ \App\Support\Number::durationDays($days) }}
</span>
