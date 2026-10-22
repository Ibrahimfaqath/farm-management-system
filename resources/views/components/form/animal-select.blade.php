@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '— Pilih —',
    'required' => false,
    'hint' => null,
    'error' => null,
    'disabled' => false,
    'searchPlaceholder' => null,
    'limit' => 10,
])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $current = old($name, $selected);
    $fieldId = 'field_'.str_replace(['[', ']'], '-', $name);
    $triggerId = $fieldId.'_trigger';
    $listId = $fieldId.'_list';
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';
    $searchPlaceholder = $searchPlaceholder ?? __('Search animals…');
    $visibleLimit = max(1, (int) $limit);
    $selectedLabel = null;

    foreach ($options as $optionValue => $optionLabel) {
        if ((string) $optionValue === (string) $current) {
            $selectedLabel = $optionLabel;
            break;
        }
    }

    $triggerLabel = $selectedLabel ?? $placeholder;
@endphp

<div {{ $attributes->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label" data-animal-select-label>
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-tone-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div class="dropdown filter-select w-full" data-animal-select data-animal-select-limit="{{ $visibleLimit }}">
        <select id="{{ $fieldId }}" name="{{ $name }}" data-animal-select-native
                @if ($disabled) disabled @endif
                @if ($required) aria-required="true" @endif
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
                @class(['select w-full bg-base-100 text-sm', 'select-error' => $hasError])>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>

        <button type="button" hidden id="{{ $triggerId }}"
                class="select w-full bg-base-100 text-left text-sm @if ($hasError) select-error @endif"
                data-animal-select-trigger
                aria-haspopup="listbox"
                aria-expanded="false"
                aria-controls="{{ $listId }}"
                @if ($required) aria-required="true" @endif
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
                @if ($disabled) disabled @endif>
            <span class="truncate" data-animal-select-value>{{ $triggerLabel }}</span>
        </button>

        <div hidden class="dropdown-content z-50 mt-1 w-full overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-lg"
             data-animal-select-panel>
            <div class="border-b border-base-300 p-2">
                <input type="search" class="input input-sm w-full bg-base-100"
                       placeholder="{{ $searchPlaceholder }}"
                       aria-label="{{ $searchPlaceholder }}"
                       autocomplete="off"
                       data-animal-select-search>
            </div>

            <ul id="{{ $listId }}" role="listbox" tabindex="-1"
                aria-label="{{ $label ?? $placeholder }}"
                class="max-h-64 overflow-y-auto p-1 text-nav"
                data-animal-select-list>
                @if ($placeholder !== null)
                    <li role="option" data-animal-select-option data-value=""
                        aria-selected="{{ $selectedLabel === null ? 'true' : 'false' }}"
                        class="{{ $selectedLabel === null ? 'flex cursor-pointer items-center rounded-field px-2.5 py-1.5 leading-5 text-primary' : 'flex cursor-pointer items-center rounded-field px-2.5 py-1.5 leading-5' }}">
                        <span class="truncate">{{ $placeholder }}</span>
                    </li>
                @endif
                @foreach ($options as $optionValue => $optionLabel)
                    @php
                        $isSelected = (string) $optionValue === (string) $current;
                        $isVisible = $loop->index < $visibleLimit || $isSelected;
                    @endphp
                    <li role="option" data-animal-select-option data-value="{{ $optionValue }}"
                        @if (! $isVisible) hidden @endif
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        class="{{ $isSelected ? 'flex cursor-pointer items-center rounded-field px-2.5 py-1.5 leading-5 text-primary' : 'flex cursor-pointer items-center rounded-field px-2.5 py-1.5 leading-5' }}">
                        <span class="truncate">{{ $optionLabel }}</span>
                    </li>
                @endforeach
            </ul>

            <p hidden class="px-3 py-2 text-xs text-base-content/55" data-animal-select-empty>
                {{ __('No animals found.') }}
            </p>
        </div>
    </div>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-tone-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
