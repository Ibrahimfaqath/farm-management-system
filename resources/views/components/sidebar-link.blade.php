@props(['item', 'label' => null])

{{--
    A single destination in the sidebar.

    It is shared by the nested list inside a category and by categories that
    have only one visible destination: those are rendered as a direct link
    instead of a disclosure that opens to reveal itself. Keeping the active
    state here means both shapes stay in step.

    A one-item category passes its own label so the flattened entry keeps the
    familiar top-level name while still using the destination's icon.

    @example <x-sidebar-link :item="$item" />
--}}

@php
    $isActive = \App\Support\Navigation::itemIsActive($item);
@endphp

<li>
    <a href="{{ route($item['route']) }}"
       @if ($isActive) class="menu-active" aria-current="page" data-sidebar-active @endif>
        <x-icon :name="$item['icon']" :size="16" />
        <span class="truncate">{{ $label ?? $item['label'] }}</span>
    </a>
</li>
