@props(['variant' => 'button'])

@php
    $toDark = __('Switch to dark theme');
    $toLight = __('Switch to light theme');
@endphp

{{--
    Theme switcher for the two shipped DaisyUI themes.

    One button that flips between them: pressing it swaps the theme and stores
    the choice. The earlier version opened a menu with two radio options, which
    asked for a click to reach a control that could not offer anything else —
    with exactly two themes a toggle is both fewer clicks and less clutter in
    the header.

    The icon shows the theme you would get by pressing it (a sun while light, a
    moon while dark), which is how most apps and the OS behave. The label says
    the same thing in words, so the button is not colour- or glyph-only.

    The inline script in `x-theme-head` applies the stored theme before paint;
    the one below only keeps the button in step with it.
--}}

@if ($variant === 'toggle')
    {{-- Settings page: the same switch, spelled out. --}}
    <label class="flex cursor-pointer items-center justify-between gap-3">
        <span class="min-w-0">
            <span class="block text-sm font-medium">{{ __('Dark theme') }}</span>
            <span class="block text-xs text-base-content/60">{{ __('Use the black palette at night.') }}</span>
        </span>
        <input type="checkbox" class="theme-controller toggle toggle-primary"
               aria-label="{{ __('Dark theme') }}" data-theme-toggle>
    </label>
@else
    <button type="button" class="btn btn-ghost btn-sm btn-square"
            data-theme-toggle-click
            data-label-dark="{{ $toDark }}"
            data-label-light="{{ $toLight }}"
            aria-pressed="false"
            title="{{ $toDark }}">
        <x-icon name="sun" :size="18" data-theme-icon="sun" />
        <x-icon name="moon" :size="18" data-theme-icon="moon" />
        <span class="sr-only" data-theme-toggle-label>{{ $toDark }}</span>
    </button>
@endif

@once
    @push('scripts')
        <script>
            (function () {
                var STORAGE_KEY = 'farm-theme';
                var THEMES = ['light', 'black'];

                function isValid(value) {
                    return THEMES.indexOf(value) !== -1;
                }

                function current() {
                    var attr = document.documentElement.getAttribute('data-theme');
                    return isValid(attr) ? attr : 'light';
                }

                function apply(theme, persist) {
                    var dark = theme === 'black';

                    document.documentElement.setAttribute('data-theme', theme);

                    if (persist) {
                        try {
                            localStorage.setItem(STORAGE_KEY, theme);
                        } catch (error) {
                            /* Private mode or blocked storage: the choice still applies for this page. */
                        }
                    }

                    document.querySelectorAll('[data-theme-toggle]').forEach(function (input) {
                        input.checked = dark;
                    });

                    // The glyph is not touched here. `app.css` shows the moon
                    // whenever `<html data-theme="black">`, so the icon is right
                    // at first paint and this stays a single source of truth.

                    document.querySelectorAll('[data-theme-toggle-click]').forEach(function (button) {
                        // The control describes the action it performs, not the
                        // state it currently sits in.
                        var text = dark
                            ? button.getAttribute('data-label-light')
                            : button.getAttribute('data-label-dark');

                        button.setAttribute('aria-pressed', dark ? 'true' : 'false');
                        button.setAttribute('title', text);

                        var label = button.querySelector('[data-theme-toggle-label]');

                        if (label) {
                            label.textContent = text;
                        }
                    });
                }

                document.addEventListener('click', function (event) {
                    var button = event.target.closest && event.target.closest('[data-theme-toggle-click]');

                    if (button) {
                        event.preventDefault();
                        apply(current() === 'black' ? 'light' : 'black', true);
                    }
                });

                document.addEventListener('change', function (event) {
                    var input = event.target;

                    // The settings control is a checkbox, not a button.
                    if (input.matches && input.matches('[data-theme-toggle]')) {
                        apply(input.checked ? 'black' : 'light', true);
                    }
                });

                window.FarmTheme = {
                    apply: apply,
                    current: current,
                    toggle: function () {
                        apply(current() === 'black' ? 'light' : 'black', true);
                    }
                };

                apply(current(), false);
            })();
        </script>
    @endpush
@endonce