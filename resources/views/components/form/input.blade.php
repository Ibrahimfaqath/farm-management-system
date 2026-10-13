@props([
    'name',
    'label' => null,
    'value' => null,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'step' => null,
    'min' => null,
    'max' => null,
    'disabled' => false,
    'readonly' => false,
])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $fieldId = 'field_'.str_replace(['[', ']'], '-', $name);
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';

    // Extra attributes (autocomplete, data-*, …) belong on the control itself.
    // Previously they were echoed as raw HTML next to the component's own
    // class attribute, which produced two `class` attributes and silently
    // dropped the DaisyUI input styling. Utility classes given to the component
    // (grid spans, widths) are applied to both the wrapper and the control so
    // existing call sites keep working.
    $utilityClasses = $attributes->get('class');
    $controlAttributes = $attributes->except(['class'])
        ->merge(['class' => 'input w-full bg-base-100 text-sm'])
        ->class([$utilityClasses]);
@endphp

<div {{ $attributes->only('class')->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-tone-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
           @if (old($name) !== null) value="{{ old($name) }}" @elseif ($value !== null) value="{{ $value }}" @endif
           @if ($required) required @endif
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @if ($step) step="{{ $step }}" @endif
           @if ($min !== null) min="{{ $min }}" @endif
           @if ($max !== null) max="{{ $max }}" @endif
           @if ($disabled) disabled @endif
           @if ($readonly) readonly @endif
           @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
           {{ $controlAttributes->class([$hasError ? 'input-error' : null]) }}>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-tone-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
