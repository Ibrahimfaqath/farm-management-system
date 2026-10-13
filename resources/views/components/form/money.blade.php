@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
    'step' => '1000',
    'min' => 0,
])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $current = old($name, $value ?? 0);
    $fieldId = 'field_'.str_replace(['[', ']'], '-', $name);
    $hintId = $fieldId.'_hint';
    $errorId = $fieldId.'_error';

    $utilityClasses = $attributes->get('class');
    $controlAttributes = $attributes->except(['class'])
        ->merge(['class' => 'input join-item w-full bg-base-100 text-sm tabular-nums'])
        ->class([$utilityClasses]);
@endphp

<div {{ $attributes->only('class')->class(['form-field w-full']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="field-label">
            {{ $label }}
            @if ($required)<span class="ml-0.5 text-tone-error" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div class="join w-full">
        <span class="join-item grid place-items-center border border-base-300 bg-base-200 px-3 text-xs font-medium text-base-content/60">Rp</span>
        <input id="{{ $fieldId }}" name="{{ $name }}" type="number" inputmode="numeric"
               value="{{ is_numeric($current) ? (int) round((float) $current) : 0 }}"
               @if ($required) required @endif
               @if ($min !== null) min="{{ $min }}" @endif
               @if ($step) step="{{ $step }}" @endif
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
               {{ $controlAttributes->class([$hasError ? 'input-error' : null]) }}>
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