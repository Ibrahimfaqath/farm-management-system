@props([
    'label' => null,
    'align' => 'dropdown-end',
    'offset' => 'mt-1',
    'width' => 'w-52',
])

{{--
    Row action menu.

    One DaisyUI `dropdown` per table row, so a list can offer several actions
    without stretching the row or pushing the table wider. The trigger and the
    panel follow the DaisyUI 5 focus-within pattern: no JavaScript is needed to
    open it, which is why the trigger and the panel both carry `tabindex="0"`.

    Put the item links or `POST` forms inside as `menu` list items, for example:

        <x-actions-menu :label="__('Actions for :code', ['code' => $animal->animal_code])">
            <li><a href="{{ route('animals.edit', $animal) }}">…</a></li>
        </x-actions-menu>

    @see resources/js/app.js for the outside-click and Escape handling that
         closes the panel again.
--}}

<div {{ $attributes->class(['dropdown', $align, 'row-menu']) }}>
    <button type="button" tabindex="0"
            class="btn btn-ghost btn-xs btn-square row-menu-trigger"
            aria-label="{{ $label ?? __('Actions') }}"
            aria-haspopup="true" aria-expanded="false">
        <x-icon name="more" :size="16" />
    </button>

    <ul tabindex="0" role="menu"
        class="menu dropdown-content z-50 {{ $offset }} {{ $width }} flex-nowrap rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
        {{ $slot }}
    </ul>
</div>
