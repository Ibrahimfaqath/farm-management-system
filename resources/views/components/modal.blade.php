@props(['id', 'title', 'description' => null, 'size' => 'max-w-lg'])

{{--
    The one dialog pattern in the app.

    Opening and closing go through `data-modal-open` / `data-modal-close` /
    the `modal-backdrop` form, handled once in app.js, so pages stop repeating
    inline `onclick` handlers. `<dialog>` gives Escape-to-close, focus trapping
    and inert background for free.
--}}

<dialog id="{{ $id }}" class="modal" {{ $attributes }}>
    <div class="modal-box {{ $size }}">
        <h3 class="text-base font-semibold" id="{{ $id }}-title">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-xs text-base-content/60">{{ $description }}</p>
        @endif

        {{ $slot }}
    </div>
    <form method="dialog" class="modal-backdrop"><button>{{ __('Close') }}</button></form>
</dialog>