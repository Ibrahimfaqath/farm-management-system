<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? __('Dashboard') }} &middot; FARM</title>
    <x-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans">
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle">

    <div class="drawer-content flex min-h-dvh flex-col">
        <header class="sticky top-0 z-30 border-b border-base-300 bg-base-100">
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

                <div class="flex flex-none items-center gap-1">
                    <x-global-alerts />
                    <x-theme-switcher />

                    <x-account-menu />
                </div>
            </div>
        </header>

        <main class="flex-1 px-3 py-4 sm:px-5 sm:py-6">
            @if (session('status'))
                <div role="status" class="alert alert-success alert-soft mb-4 py-2 text-sm">
                    <x-icon name="success" :size="16" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('_form'))
                <div role="alert" class="alert alert-error alert-soft mb-4 py-2 text-sm">
                    <x-icon name="error" :size="16" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="mx-auto w-full max-w-[1400px]">
                @yield('content')
            </div>
        </main>

        <footer class="text-meta border-t border-base-300 px-5 py-3 text-base-content/45 print:hidden">
            FARM &middot; {{ now()->translatedFormat('d M Y') }}
        </footer>
    </div>

    <aside class="drawer-side z-40">
        <label for="app-drawer" aria-label="{{ __('Close menu') }}" class="drawer-overlay"></label>

        <div class="flex h-dvh w-64 flex-col overflow-hidden border-r border-base-300 bg-base-100 lg:w-64">
            <div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-base-300 px-4">
                <span class="grid size-8 shrink-0 place-items-center rounded-field bg-primary text-sm font-bold text-primary-content">F</span>
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
                <ul class="menu menu-md w-full gap-0.5 p-0 text-nav">
                    @foreach (\App\Support\Navigation::forUser(auth()->user()) as $group)
                        <li class="text-label menu-title mt-2 px-3 text-base-content/65 first:mt-0">
                            {{ $group['label'] }}
                        </li>
                        @foreach ($group['items'] as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @if ($isActive) class="menu-active" aria-current="page" @endif>
                                    <x-icon :name="$item['icon']" :size="16" />
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
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