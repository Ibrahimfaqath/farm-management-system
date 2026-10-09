@props(['name', 'label' => null, 'value' => null, 'type' => 'text', 'required' => false, 'hint' => null, 'error' => null, 'placeholder' => null, 'step' => null, 'min' => null, 'max' => null, 'attributes' => ''])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
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

    <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
           @if (old($name) !== null) value="{{ old($name) }}" @elseif ($value !== null) value="{{ $value }}" @endif
           @if ($required) required @endif
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @if ($step) step="{{ $step }}" @endif
           @if ($min !== null) min="{{ $min }}" @endif
           @if ($max !== null) max="{{ $max }}" @endif
           @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
           {!! $attributes !!}
           class="input w-full bg-base-100 text-sm @if ($hasError) input-error @endif">

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
