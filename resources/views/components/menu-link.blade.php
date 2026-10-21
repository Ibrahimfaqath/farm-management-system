@props([
    'href',
    'icon' => null,
])

{{--
    A single entry inside an `x-actions-menu`.

    Keeps every row menu identical: same icon size, same gap, same colour, and a
    real `href` so the entry can be opened in a new tab and read by assistive
    technology.

    @example <x-menu-link :href="route('animals.show', $animal)" icon="info">{{ __('Details') }}</x-menu-link>
--}}

<li>
    <a href="{{ $href }}" {{ $attributes->class(['flex items-center gap-2']) }}>
        @if ($icon)
            <x-icon :name="$icon" :size="15" class="text-base-content/50" />
        @endif
        {{ $slot }}
    </a>
</li>
