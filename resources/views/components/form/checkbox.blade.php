@props(['name', 'label', 'hint' => null, 'checked' => false, 'value' => 1, 'disabled' => false])

@php $fieldId = 'field_'.$name; @endphp

<div {{ $attributes->class(['form-field w-full']) }}>
    <label for="{{ $fieldId }}"
           class="flex cursor-pointer items-start gap-2.5 rounded-field border border-base-300 bg-base-100 px-3 py-2.5 transition-colors hover:border-base-content/25 has-checked:border-primary has-checked:bg-primary/5">
        <input id="{{ $fieldId }}" type="checkbox" name="{{ $name }}" value="{{ $value }}"
               @checked(old($name, $checked))
               @disabled($disabled)
               class="checkbox checkbox-sm checkbox-primary mt-0.5">
        <span class="text-sm">
            <span class="block font-medium leading-tight">{{ $label }}</span>
            @if ($hint)<span class="mt-0.5 block text-xs text-base-content/55">{{ $hint }}</span>@endif
        </span>
    </label>

    @error($name)
        <p class="mt-1 flex items-start gap-1 text-xs text-error">
            <x-icon name="error" :size="13" class="mt-px" />
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
