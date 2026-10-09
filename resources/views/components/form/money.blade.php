@props(['name', 'label' => null, 'value' => null, 'required' => false, 'hint' => null, 'error' => null, 'attributes' => 'min="0" step="1000"'])

@php
    $errorMessage = $error ?? $errors->first($name);
    $hasError = filled($errorMessage);
    $current = old($name, $value ?? 0);
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

    <div class="join w-full">
        <span class="join-item grid place-items-center border border-base-300 bg-base-200 px-3 text-xs font-medium text-base-content/60">Rp</span>
        <input id="{{ $fieldId }}" name="{{ $name }}" type="number" inputmode="numeric"
               value="{{ is_numeric($current) ? (int) round((float) $current) : 0 }}"
               @if ($required) required @endif
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @elseif ($hint) aria-describedby="{{ $hintId }}" @endif
               {!! $attributes !!}
               class="input join-item w-full bg-base-100 text-sm tabular-nums @if ($hasError) input-error @endif">
    </div>

    @if ($hasError)
        <p id="{{ $errorId }}" class="mt-1 flex items-start gap-1 text-xs text-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-base-content/50">{{ $hint }}</p>
    @endif
</div>
