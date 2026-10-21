@props([
    'name',
    'label' => null,
    'options' => [],
    'optionAttributes' => [],
    'selected' => null,
    'placeholder' => '— Pilih —',
    'required' => false,
    'hint' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $current = old($name, $selected);
    $fieldId = 'field_'.str_replace(['[', ']'], '-', $name);
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';

    $utilityClasses = $attributes->get('class');
    $controlAttributes = $attributes->except(['class'])
        ->merge(['class' => 'select w-full bg-base-100 text-sm'])
        ->class([$utilityClasses]);
@endphp

<div {{ $attributes->only('class')->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-tone-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select id="{{ $fieldId }}" name="{{ $name }}" @if ($required) required @endif @if ($disabled) disabled @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
            {{ $controlAttributes->class([$hasError ? 'select-error' : null]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)
                    @foreach (($optionAttributes[$optionValue] ?? []) as $attribute => $attributeValue) {{ $attribute }}="{{ $attributeValue }}" @endforeach>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-tone-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>