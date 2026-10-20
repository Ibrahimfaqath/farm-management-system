@php
    $user = auth()->user();
@endphp

{{--
    Account menu.

    A dropdown is read in one glance, so the panel is built on one vertical
    rhythm instead of a list of rows:

      identity    who is signed in
      actions     what they can do
      preference  the language they read the app in
      danger      leaving

    What made the old version look untidy, measured rather than guessed:

    * Three different left insets — 10px for the identity and the "Language"
      label, 12px for the rows, and the language control starting at 0. Text
      therefore had no single edge to line up on.
    * Rows were 33px tall, under the 40px the header uses for the same kind of
      target.
    * Each of the three separators carried 6px of menu padding above and below
      its 1px line: 51px of the panel's height went to whitespace around rules.
    * `menu-none` was written on six elements and defined nowhere. It did
      nothing, so non-interactive rows kept DaisyUI's menu padding *and* its
      hover fill — the identity block lit up like a button.

    It is a plain panel now, not a `ul.menu`: nothing here is a menu widget, so
    DaisyUI's row padding and hover had to be undone on every line. Sections are
    divided by a real border that runs edge to edge, which is both tidier and
    cheaper than a separator row.
--}}

<div {{ $attributes->class(['account-menu dropdown dropdown-end']) }}>
    <button type="button" tabindex="0" class="btn btn-ghost btn-sm h-10 gap-2 px-2"
            aria-label="{{ __('Account menu') }}: {{ $user->name }}"
            aria-expanded="false" aria-controls="account-menu-panel">
        {{--
            The square is set explicitly rather than left to DaisyUI.

            `.avatar > div { aspect-ratio: 1 }` is what makes the disc round, and
            a `<span>` child never received it — so the badge rendered 28×16, a
            stretched oval, and only `w-7` was applying. Declaring `size-8` with
            a grid centring it does not depend on that internals rule, and the
            shape cannot quietly regress if it changes.
        --}}
        <span class="avatar avatar-placeholder shrink-0">
            <span class="text-meta grid size-8 place-items-center rounded-full bg-primary/15 font-semibold text-primary">
                {{ $user->initials }}
            </span>
        </span>
        <span class="hidden text-left leading-tight sm:block">
            <span class="block max-w-32 truncate text-xs font-medium">{{ $user->name }}</span>
            <span class="text-meta block text-base-content/55">{{ $user->role_label }}</span>
        </span>
        {{-- The row opens a panel, and a ghost button carrying a name does not say so.
             The caret is what makes that legible without opening it first. --}}
        <x-icon name="chevron-down" :size="14" class="hidden shrink-0 text-base-content/40 sm:block" />
    </button>

    {{--
        `tabindex="-1"`, not `0`.

        DaisyUI opens the panel from `:focus-within`, so the container has to be
        focusable at all: clicking the identity block is a click on inert text,
        and without a focusable ancestor the browser blurs the trigger and the
        panel shuts under the pointer. But `0` also puts the container itself in
        the tab order, so a keyboard user had to press Tab once on an empty panel
        before reaching "Settings". `-1` keeps it click-focusable and skips it.
    --}}
    <div id="account-menu-panel" tabindex="-1"
         class="dropdown-content z-50 mt-2 w-72 overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-lg">
        {{--
            Identity.

            36px disc rather than the trigger's 32px: inside the panel the
            identity is the subject of the section, not an icon beside a label.
            The role rides next to the name as a quiet badge because the trigger
            hides the name below `sm`, and "which account is this" is the first
            question a shared device raises.
        --}}
        <div class="flex items-center gap-3 px-3 py-3">
            <span class="text-meta grid size-9 shrink-0 place-items-center rounded-full bg-primary/15 font-semibold text-primary">
                {{ $user->initials }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex min-w-0 items-center gap-2">
                    <p class="truncate text-sm font-semibold leading-snug">{{ $user->name }}</p>
                    <span class="badge badge-ghost badge-sm shrink-0">{{ $user->role_label }}</span>
                </div>
                <p class="text-meta truncate text-base-content/55">{{ $user->email }}</p>
            </div>
        </div>

        @if ($user->canAccessModule('settings') && \Illuminate\Support\Facades\Route::has('settings.index'))
            <div class="border-t border-base-300 p-1">
                <a href="{{ route('settings.index') }}" class="account-menu-row">
                    <x-icon name="settings" :size="16" class="shrink-0 text-base-content/50" />
                    <span class="truncate">{{ __('Settings') }}</span>
                </a>
            </div>
        @endif

        {{--
            Language.

            `tabs tabs-box` is a segmented control built from the theme's own
            tokens: a base-200 track, the current option lifted onto base-100 by
            its `aria-current`, 40px targets. It replaces two `btn` segments
            whose `btn-active` fill was `base-200` — on a white panel that read
            as a disabled button rather than a selected one.

            One form holds both options (see LanguageControlTest): the button
            itself carries `name="locale"`, so each half of the control is a
            real target with no dead space beside it. `role="group"`, not
            `tablist`: these submit and navigate, they do not switch panels.
        --}}
        <div class="border-t border-base-300 px-3 py-2.5">
            <p class="text-label mb-2 text-base-content/50">{{ __('Language') }}</p>
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                @method('PUT')
                <div class="tabs tabs-box w-full" role="group" aria-label="{{ __('Language') }}">
                    @foreach (\App\Support\Locale::SUPPORTED as $code)
                        <button type="submit" name="locale" value="{{ $code }}"
                                class="tab flex-1 basis-0 text-xs"
                                @if (app()->getLocale() === $code) aria-current="true" @endif>
                            {{ $code === 'id' ? __('Indonesian') : __('English') }}
                        </button>
                    @endforeach
                </div>
            </form>
        </div>

        <div class="border-t border-base-300 p-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-submit-guard class="account-menu-row text-tone-error">
                    <x-icon name="logout" :size="16" class="shrink-0" />
                    <span class="truncate">{{ __('Log out') }}</span>
                </button>
            </form>
        </div>
    </div>
</div>
