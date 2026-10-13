{{--
    Submit buttons for real forms.

    The guard disables the button on the first submit so a double click or an
    impatient Enter key cannot post the same transaction twice — important for
    the money, stock and livestock forms where a duplicate POST has business
    consequences.

    @example <x-form.submit>{{ __('Save') }}</x-form.submit>
    @example <x-form.submit variant="danger" :loading="true">{{ __('Save') }}</x-form.submit>
--}}

@props([
    'variant' => 'primary',
    'size' => 'sm',
    'loading' => false,
    'loadingLabel' => null,
])

<button type="submit" {{ $attributes->class(['btn', 'btn-'.$variant, $size ? 'btn-'.$size : null]) }}
        @if ($loading) disabled @endif data-submit-guard>
    @if ($loading)
        <x-icon name="loading" :size="16" class="animate-spin" />
        <span>{{ $loadingLabel ?? $slot }}</span>
    @else
        {{ $slot }}
    @endif
</button>