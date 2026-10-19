@php
    $user = auth()->user();
@endphp

{{--
    Account menu.

    Rebuilt as one component so the navbar stays readable. What changed:

    * The identity line was a `menu-title`, which gave it the same small grey
      uppercase treatment as the "Language" heading underneath — two different
      kinds of row that looked identical. Name and address now sit in one plain
      block.
    * The current language used `menu-active`, which fills the row with solid
      `base-content`: a black bar in the middle of a light menu. It is now a
      quiet check plus medium weight, on a non-wrapping row.
    * Rows are compact and the groups separated, so the menu reads as
      "who am I / what can I do / what language" instead of a flat list.
--}}

<div {{ $attributes->class(['dropdown dropdown-end']) }}>
    <button type="button" tabindex="0" class="btn btn-ghost btn-sm h-10 gap-2 px-2"
            aria-label="{{ __('Account menu') }}" aria-haspopup="true" aria-expanded="false">
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

    <ul tabindex="0" class="menu dropdown-content z-50 mt-2 w-72 flex-nowrap rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
        <li class="menu-none">
            <span class="block cursor-default px-2.5 py-1.5">
                <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                <span class="text-meta block truncate text-base-content/55">{{ $user->email }}</span>
            </span>
        </li>

        <li role="separator" class="menu-none"><div class="my-0.5 border-t border-base-300"></div></li>

        @if ($user->canAccessModule('settings') && \Illuminate\Support\Facades\Route::has('settings.index'))
            <li>
                <a href="{{ route('settings.index') }}" class="flex-nowrap gap-2">
                    <x-icon name="settings" :size="16" class="shrink-0 text-base-content/50" />
                    <span class="truncate">{{ __('Settings') }}</span>
                </a>
            </li>
        @endif

        <li role="separator" class="menu-none"><div class="my-0.5 border-t border-base-300"></div></li>

        <li class="menu-none">
            <span class="text-label flex flex-nowrap items-center gap-2 px-2.5 pb-1.5 pt-2 font-medium text-base-content/50">
                <x-icon name="language" :size="14" class="shrink-0" />
                {{ __('Language') }}
            </span>
        </li>

        {{--
            A segmented control, not a stack of rows.

            Each option used to be its own form with its own button, and the
            button was only as wide as its text: the `li` and the form measured
            230px while the button measured 62px, leaving up to 156px of dead
            space on the right that silently did nothing when clicked. One form
            holding two buttons that each fill their half removes the dead
            space, halves the DOM, and shows both options at once instead of
            hiding the choice behind a list.
        --}}
        <li class="menu-none">
            <form method="POST" action="{{ route('locale.update') }}" class="block p-0">
                @csrf
                @method('PUT')
                <div class="join w-full" role="group" aria-label="{{ __('Language') }}">
                    @foreach (\App\Support\Locale::SUPPORTED as $code)
                        <button type="submit" name="locale" value="{{ $code }}"
                                @class([
                                    'join-item btn btn-sm min-w-0 flex-1 basis-0 gap-1.5 px-2 text-xs font-normal',
                                    'btn-active' => app()->getLocale() === $code,
                                    'btn-ghost border-base-300' => app()->getLocale() !== $code,
                                ])
                                @if (app()->getLocale() === $code) aria-current="true" @endif>
                            <span class="text-label shrink-0 opacity-70">{{ str($code)->upper() }}</span>
                            <span class="truncate">{{ $code === 'id' ? __('Indonesian') : __('English') }}</span>
                        </button>
                    @endforeach
                </div>
            </form>
        </li>

        <li role="separator" class="menu-none"><div class="my-0.5 border-t border-base-300"></div></li>

        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-submit-guard class="flex-nowrap gap-2 text-tone-error">
                    <x-icon name="logout" :size="16" class="shrink-0" />
                    <span class="truncate">{{ __('Log out') }}</span>
                </button>
            </form>
        </li>
    </ul>
</div>