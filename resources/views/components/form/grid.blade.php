@props(['cols' => 2])

<div {{ $attributes->class(["grid grid-cols-1 gap-4 sm:grid-cols-{$cols}"]) }}>{{ $slot }}</div>
