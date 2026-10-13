@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
    'required' => false,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'disabled' => false,
])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $fieldId = 'field_'.str_replace(['[', ']'], '-', $name);
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';

    $utilityClasses = $attributes->get('class');
    $controlAttributes = $attributes->except(['class'])
        ->merge(['class' => 'textarea w-full bg-base-100 text-sm'])
        ->class([$utilityClasses]);
@endphp

<div {{ $attributes->only('class')->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-tone-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif @if ($disabled) disabled @endif
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
              {{ $controlAttributes->class([$hasError ? 'textarea-error' : null]) }}>{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-tone-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>