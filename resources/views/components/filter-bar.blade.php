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
    $hasFilters = collect(request()->query())->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
@endphp

{{--
    The shared filter bar for index pages.

    Query keys stay flat (`?search=…&status=…`) because that is what every
    index controller already reads. The bar owns the search affordance, the
    apply action, and a "Clear filters" link that only shows up when a filter
    is actually active, so an unfiltered list stays quiet.
--}}

<form method="GET" action="{{ url()->current() }}" role="search"
      {{ $attributes->class(['grid items-center gap-2 border-b border-base-300 p-3', $columns]) }}>
    @if ($withSearch)
        <label class="input input-sm w-full bg-base-100">
            <x-icon name="search" :size="15" class="text-base-content/45" />
            <input type="search" name="{{ $search }}" value="{{ request()->query($search, '') }}"
                   placeholder="{{ $searchPlaceholder ?? __('Search') }}" aria-label="{{ __('Search') }}">
        </label>
    @endif

    {{ $slot }}

    <div class="flex items-center gap-2">
        <button type="submit" class="btn btn-primary btn-sm">{{ $applyLabel ?? __('Apply') }}</button>
        @if ($hasFilters)
            <a href="{{ $resetUrl }}" class="btn btn-ghost btn-sm">{{ __('Clear filters') }}</a>
        @endif
    </div>
</form>