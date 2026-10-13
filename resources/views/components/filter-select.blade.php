@props([
    'name',
    'options' => [],
    'label' => null,
    'placeholder' => null,
    'selected' => null,
    'size' => 'select-sm',
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

<div class="dropdown filter-select w-full" data-filter-select>
    <select name="{{ $name }}" class="select {{ $size }} w-full bg-base-100" data-filter-select-native
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
    <ul tabindex="-1" role="listbox" hidden
        class="dropdown-content z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-box border border-base-300 bg-base-100 p-1 text-nav shadow-lg"
        data-filter-select-list>
        @php
            $row = 'flex cursor-pointer items-center justify-between py-1.5 pl-2.5 pr-2 leading-5 rounded-field';
        @endphp

        {{-- The "no filter" choice has to live in the list, otherwise an active
             filter could never be cleared from here. --}}
        <li role="option" data-value="" aria-selected="{{ $selectedLabel === null ? 'true' : 'false' }}" class="{{ $row }}"><span class="truncate">{{ $placeholder ?? __('All') }}</span><x-icon name="success" :size="14" class="opacity-0" data-filter-select-check /></li>
        @foreach ($options as $optionValue => $optionLabel)
            <li role="option" data-value="{{ $optionValue }}" aria-selected="{{ (string) $optionValue === (string) $current ? 'true' : 'false' }}" class="{{ $row }}"><span class="truncate">{{ $optionLabel }}</span><x-icon name="success" :size="14" class="opacity-0" data-filter-select-check /></li>
        @endforeach
    </ul>
</div>
