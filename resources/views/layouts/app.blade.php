<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? __('Dashboard') }} &middot; FARM</title>
    {{-- There was no icon link at all, so every page requested a /favicon.ico
         that does not exist and got a 404 in return. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark-black.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-mark-black.png') }}">
    <x-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans">
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle">

    <div class="drawer-content flex min-h-dvh flex-col">
        <header class="sticky top-0 z-30 border-b border-base-300 bg-base-100 print:hidden">
            <div class="navbar min-h-14 gap-2 px-3 sm:px-5">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" class="btn btn-ghost btn-sm btn-square" aria-label="{{ __('Open menu') }}">
                        <x-icon name="menu" :size="20" />
                    </label>
                </div>

                <div class="min-w-0 flex-1">
                    {{-- The page header below the navbar already carries the
                         title, so the bar shows breadcrumb context instead of
                         repeating it. --}}
                    @isset($moduleLabel)
                        <p class="truncate text-xs font-medium uppercase tracking-wide text-base-content/45">{{ $moduleLabel }}</p>
                    @endisset
                    <p class="truncate text-sm font-semibold leading-tight">{{ $title ?? __('Dashboard') }}</p>
                </div>

                {{-- The three controls used to float loose with `gap-1`, so the bar ended in
                     what looked like two unrelated icons and a name. A bounded
                     cluster says "these belong together", and the rule inside it
                     separates the things you act on from the thing you are. --}}
                <div class="header-controls flex flex-none items-center gap-0.5 rounded-field border border-base-300 bg-base-100 p-0.5">
                    {{--
                        The items are passed explicitly.

                        This component reached into the surrounding view for them,
                        and nothing in the markup said so. It is rendered from the
                        layout, and `@extends('layouts.app', ['title' => ...])`
                        hands the layout that array rather than the child view's
                        data — so the variable was absent and the `?? collect()`
                        fallback made the bell report "all clear" on every page
                        in the application, including the dashboard. A silent
                        fallback is what let a dead notification survive for so
                        long: it looked healthy the whole time.
                    --}}
                    <x-global-alerts :attention-items="$attentionItems ?? collect()" />
                    <x-theme-switcher />

                    <span class="mx-0.5 h-6 w-px shrink-0 bg-base-300" aria-hidden="true"></span>

                    <x-account-menu />
                </div>
            </div>
        </header>

        <main class="flex-1 px-3 py-4 sm:px-5 sm:py-6">
            {{-- The live filter line and its announcement live outside
                 `#page-content` so they survive the swap they are reporting on. --}}
            <div id="live-filter-progress" class="live-filter-progress" hidden aria-hidden="true"></div>
            <p id="live-filter-status" class="sr-only" role="status" aria-live="polite"></p>

            @if (session('status'))
                <div role="status" class="alert alert-success alert-soft motion-drop mb-4 py-2 text-sm">
                    <x-icon name="success" :size="16" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('_form'))
                <div role="alert" class="alert alert-error alert-soft motion-drop mb-4 py-2 text-sm">
                    <x-icon name="error" :size="16" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div id="page-content" class="motion-page mx-auto w-full max-w-[1400px]"
                 data-live-filter-message="{{ __('Filters updated') }}">
                @yield('content')
            </div>
        </main>

        <footer class="text-meta border-t border-base-300 px-5 py-3 text-base-content/45 print:hidden">
            FARM &middot; {{ now()->translatedFormat('d M Y') }}
        </footer>
    </div>

    <aside class="drawer-side z-40 print:hidden">
        <label for="app-drawer" aria-label="{{ __('Close menu') }}" class="drawer-overlay"></label>

        <div class="flex h-dvh w-64 flex-col overflow-hidden border-r border-base-300 bg-base-100 lg:w-64">
            <div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-base-300 px-4">
                {{-- The mark, not the wordmark. The bottle and drop carry the brand at 32px;
                     "FARM" is already set beside it in the interface's own
                     typeface, and a second wordmark inside a 32px image would
                     be unreadable and would fight the text. The colour is
                     swapped by `data-theme` in `app.css`. --}}
                <span class="logo-mark size-8 shrink-0" role="img" aria-label="FARM"></span>
                <span class="min-w-0 flex-1 leading-tight">
                    <span class="block truncate text-sm font-bold tracking-wide">FARM</span>
                    <span class="text-meta block truncate text-base-content/50">{{ __('Farm Management System') }}</span>
                </span>

                {{-- Explicit close control for the mobile drawer; the overlay and
                     the navbar toggle remain, but keyboard and screen-reader users
                     need a button they can reach. --}}
                <button type="button" class="btn btn-ghost btn-sm btn-square lg:hidden"
                        data-drawer-close aria-label="{{ __('Close menu') }}">
                    <x-icon name="close" :size="18" />
                </button>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-3" data-drawer-nav
                 aria-label="{{ __('Main navigation') }}">
                <ul class="menu menu-md w-full gap-1 p-0 text-nav">
                    @foreach (\App\Support\Navigation::forUser(auth()->user()) as $group)
                        @php
                            $groupActive = collect($group['items'])->contains(
                                fn (array $item) => \App\Support\Navigation::itemIsActive($item)
                            );
                        @endphp
                        <li>
                            <details @if ($groupActive) open @endif class="group">
                                <summary class="text-label list-none text-base-content/65">
                                    <x-icon :name="$group['icon']" :size="16" />
                                    <span class="truncate">{{ $group['label'] }}</span>
                                    <x-icon name="chevron-down" :size="14"
                                            class="ml-auto shrink-0 text-base-content/40 transition-transform group-open:rotate-180" />
                                </summary>
                                <ul class="menu w-full gap-0.5 p-0">
                                    @foreach ($group['items'] as $item)
                                        @php $isActive = \App\Support\Navigation::itemIsActive($item); @endphp
                                        <li>
                                            <a href="{{ route($item['route']) }}"
                                               @if ($isActive) class="menu-active" aria-current="page" data-sidebar-active @endif>
                                                <x-icon :name="$item['icon']" :size="16" />
                                                <span class="truncate">{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="text-meta shrink-0 border-t border-base-300 px-4 py-3 text-base-content/45">
                <p class="font-medium text-base-content/60">
                    {{ __('Season') }} {{ now()->month >= 9 ? '2026/2027' : '2025/2026' }}
                </p>
                <p>{{ __('Demo data') }}</p>
            </div>
        </div>
    </aside>
</div>

@stack('scripts')
</body>
</html>