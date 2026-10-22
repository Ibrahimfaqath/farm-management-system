@props([
    'name',
    'options' => [],
    'label' => null,
    'placeholder' => null,
    'selected' => null,
    'size' => 'select-sm',
    'searchable' => false,
    'limit' => 12,
    'searchPlaceholder' => null,
])

{{--
    Filter dropdown that looks like the rest of the app.

    A native `<select>` cannot be themed: opening one adds no DOM node at all,
    the option list is painted by the operating system, so every filter opened a
    plain browser menu while the rest of the page was DaisyUI. On macOS and
    Windows that popup is even less controllable than on Linux.

    This keeps the real `<select>` in the form — it still holds the value, still
    submits with the query string every index controller already reads, and
    still works if JavaScript never runs. `app.js` then hides it and drives a
    DaisyUI `dropdown` + `menu` listbox in front, mirroring the select in both
    directions.

    @example
        <x-filter-select name="status" :options="$statusOptions" :label="__('Filter by status')" />

    @see resources/js/app.js initFilterSelects()
--}}

@php
    $current = old($name, $selected ?? request()->query($name, ''));
    $fieldId = 'filter_'.str_replace(['[', ']'], '-', $name);
    $selectedLabel = null;

    foreach ($options as $optionValue => $optionLabel) {
        if ((string) $optionValue === (string) $current) {
            $selectedLabel = $optionLabel;
            break;
        }
    }

    // A placeholder only makes sense while nothing is chosen; once a filter is
    // active the trigger should show the real choice.
    $triggerLabel = $selectedLabel ?? ($placeholder ?? __('Select…'));
@endphp

<div class="dropdown filter-select w-full" data-filter-select
     @if ($searchable) data-filter-select-searchable data-filter-select-limit="{{ max(1, (int) $limit) }}" @endif>
    <select id="{{ $fieldId }}" name="{{ $name }}" class="select {{ $size }} w-full bg-base-100" data-filter-select-native
            @if ($label) aria-label="{{ $label }}" @endif>
        @if ($placeholder !== null || $selectedLabel === null)
            <option value="">{{ $placeholder ?? __('All') }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $current)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    {{-- Styled as a DaisyUI `select` so it matches the real one it replaces —
         including the arrow, which `select` paints as a gradient background and
         makes room for with `padding-right`. Adding an icon of our own here
         put a second chevron on screen. --}}
    <button type="button" hidden
            class="select {{ $size }} w-full bg-base-100 text-left"
            data-filter-select-trigger
            aria-haspopup="listbox"
            aria-expanded="false"
            @if ($label) aria-label="{{ $label }}" @endif>
        <span class="truncate" data-filter-select-value>{{ $triggerLabel }}</span>
    </button>

    {{-- `dropdown-content` gives the DaisyUI panel; the rows are sized here
         rather than through `menu`, whose 2.5rem navigation rows are far too
         tall for a select list. --}}
    <div hidden class="dropdown-content z-50 mt-1 w-full overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-lg">
        @if ($searchable)
            <div class="border-b border-base-300 p-1.5">
                <input type="search" class="input input-sm w-full bg-base-100"
                       placeholder="{{ $searchPlaceholder ?? __('Search…') }}"
                       aria-label="{{ $searchPlaceholder ?? __('Search…') }}"
                       autocomplete="off"
                       data-filter-select-search>
            </div>
        @endif

        <ul tabindex="-1" role="listbox"
            class="max-h-64 overflow-y-auto p-1 text-nav"
            data-filter-select-list>
            @php
                // The chosen row is marked by its colour alone. A tick beside it was
                // a second, redundant signal, and the column reserved for it left
                // every label short of the panel's right edge.
                $row = 'flex cursor-pointer items-center py-1.5 px-2.5 leading-5 rounded-field';
                $chosen = $row.' text-primary';
            @endphp

            {{-- The "no filter" choice has to live in the list, otherwise an active
                 filter could never be cleared from here. --}}
            <li role="option" data-value="" aria-selected="{{ $selectedLabel === null ? 'true' : 'false' }}" class="{{ $selectedLabel === null ? $chosen : $row }}"><span class="truncate">{{ $placeholder ?? __('All') }}</span></li>
            @foreach ($options as $optionValue => $optionLabel)
                @php
                    $isSelected = (string) $optionValue === (string) $current;
                    $isVisible = ! $searchable || $loop->index < max(1, (int) $limit) || $isSelected;
                @endphp
                <li role="option" data-value="{{ $optionValue }}"
                    @if (! $isVisible) hidden @endif
                    aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                    class="{{ $isSelected ? $chosen : $row }}"><span class="truncate">{{ $optionLabel }}</span></li>
            @endforeach
        </ul>

        <p hidden class="px-3 py-2 text-xs text-base-content/55" data-filter-select-empty>
            {{ __('No matching options.') }}
        </p>
    </div>
</div>
