@props([
    'search' => 'search',
    'searchPlaceholder' => null,
    'reset' => null,
    'columns' => 'sm:grid-cols-2 lg:grid-cols-4',
    'applyLabel' => null,
    'withSearch' => true,
])

@php
    $resetUrl = $reset ?? url()->current();
    $activeFilters = collect(request()->query())->filter(fn ($value) => $value !== null && $value !== '');
    $hasFilters = $activeFilters->isNotEmpty();
    $toggleId = 'filter-toggle-'.substr(sha1(url()->current()), 0, 8);
@endphp

{{--
    The shared filter bar for index pages.

    Query keys stay flat (`?search=…&status=…`) because that is what every
    index controller already reads. The bar owns the search affordance, the
    apply action, and a "Clear filters" link that only shows up when a filter
    is actually active, so an unfiltered list stays quiet.

    On a phone the controls are collapsed behind a toggle. The animals list
    alone has six of them, and stacked full width they filled the entire first
    screen: a visitor had to scroll past the title, six stat tiles, the search
    box, every dropdown, a checkbox and the apply button before reaching a
    single row of data. Search stays visible because it is the control people
    reach for most, and the toggle opens the rest.

    The disclosure is a checkbox and a `:checked` sibling selector rather than
    `<details>`, because the desktop grid has to keep laying out exactly as
    before — the wrapper collapses to `display: contents`, which puts the
    controls back as direct grid items with no extra box at all.
--}}

<form method="GET" action="{{ url()->current() }}" role="search" data-live-filter
      {{ $attributes->class(['grid items-center gap-2 border-b border-base-300 p-3', $columns]) }}>
    @if ($withSearch)
        <label class="input input-sm w-full bg-base-100">
            <x-icon name="search" :size="15" class="text-base-content/45" />
            <input type="search" name="{{ $search }}" value="{{ request()->query($search, '') }}"
                   placeholder="{{ $searchPlaceholder ?? __('Search') }}" aria-label="{{ __('Search') }}">
        </label>
    @endif

    {{-- The checkbox has to be a direct child of the form: the `:checked ~
         .filter-fields` rule matches siblings, and nesting it inside the
         label made the toggle do nothing. --}}
    <input type="checkbox" id="{{ $toggleId }}" value="1" class="peer sr-only" @checked($hasFilters)>

    <label for="{{ $toggleId }}" class="btn btn-ghost btn-sm w-full justify-start gap-2 lg:hidden">
        <x-icon name="filter" :size="15" class="text-base-content/50" />
        <span>{{ __('Filters') }}</span>
        @if ($hasFilters)
            <span class="badge badge-primary badge-sm">{{ $activeFilters->count() }}</span>
        @endif
    </label>

    <div class="filter-fields">
        {{ $slot }}

        {{-- No `lg:contents` here on purpose. The parent already collapses, so
             this wrapper stays the grid item it always was and the buttons keep
             hugging their label. Collapsing it too made them grid items in
             their own right, and a grid item stretches to fill its column —
             which widened "Apply" from 82px to the full 274px column. --}}
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary btn-sm">{{ $applyLabel ?? __('Apply') }}</button>
            @if ($hasFilters)
                <a href="{{ $resetUrl }}" class="btn btn-ghost btn-sm" data-live-filter-reset>{{ __('Clear filters') }}</a>
            @endif
        </div>
    </div>
</form>