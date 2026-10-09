@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'placeholder' => '— Pilih —', 'required' => false, 'hint' => null, 'error' => null, 'attributes' => ''])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $current = old($name, $selected);
    $fieldId = 'field_'.$name;
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';
@endphp

<div {{ $attributes->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select id="{{ $fieldId }}" name="{{ $name }}" @if ($required) required @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
            {!! $attributes !!}
            class="select w-full bg-base-100 text-sm @if ($hasError) select-error @endif">
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
