@props(['name', 'label' => null, 'value' => null, 'rows' => 3, 'required' => false, 'hint' => null, 'error' => null, 'placeholder' => null, 'attributes' => ''])

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

    <textarea id="{{ $fieldId }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
              {!! $attributes !!}
              class="textarea w-full bg-base-100 text-sm @if ($hasError) textarea-error @endif">{{ old($name, $value) }}</textarea>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint && ! $errors->has($name))
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
