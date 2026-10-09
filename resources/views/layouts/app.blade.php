<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="farm">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Dashboard') }} &middot; FARM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans">
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle">

    <div class="drawer-content flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-base-300 bg-base-100">
            <div class="navbar min-h-14 gap-2 px-3 sm:px-5">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" class="btn btn-ghost btn-sm btn-square" aria-label="{{ __('Open menu') }}">
                        <x-icon name="menu" :size="20" />
                    </label>
                </div>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-sm font-semibold leading-tight">{{ $title ?? __('Dashboard') }}</h1>
                    @isset($subtitle)
                        <p class="truncate text-xs text-base-content/55">{{ $subtitle }}</p>
                    @endisset
                </div>

                <div class="flex flex-none items-center gap-1">
                    <x-global-alerts />

                    <div class="dropdown dropdown-end">
                        <button tabindex="0" class="btn btn-ghost btn-sm gap-2 px-2" type="button"
                                aria-label="{{ auth()->user()->name }}">
                            <span class="avatar avatar-placeholder">
                                <span class="w-7 rounded-full bg-primary/15 text-[11px] font-semibold text-primary">
                                    {{ auth()->user()->initials }}
                                </span>
                            </span>
                            <span class="hidden text-left leading-tight sm:block">
                                <span class="block max-w-32 truncate text-xs font-medium">{{ auth()->user()->name }}</span>
                                <span class="block text-[11px] text-base-content/55">{{ auth()->user()->role_label }}</span>
                            </span>
                        </button>

                        <ul tabindex="0" class="menu dropdown-content z-50 mt-2 w-60 rounded-box border border-base-300 bg-base-100 p-2 shadow-lg">
                            <li class="menu-title truncate px-3 text-xs font-normal" title="{{ auth()->user()->email }}">
                                {{ auth()->user()->email }}
                            </li>

                            @if (auth()->user()->canAccessModule('settings') && \Illuminate\Support\Facades\Route::has('settings.index'))
                                <li>
                                    <a href="{{ route('settings.index') }}">
                                        <x-icon name="settings" :size="16" />
                                        {{ __('Settings') }}
                                    </a>
                                </li>
                            @endif

                            <li class="menu-title px-3 text-xs">{{ __('Language') }}</li>
                            @foreach (\App\Support\Locale::SUPPORTED as $code)
                                @php $isCurrent = app()->getLocale() === $code; @endphp
                                <li>
                                    <form method="POST" action="{{ route('locale.update') }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="locale" value="{{ $code }}">
                                        <button type="submit" class="w-full justify-between" @disabled($isCurrent)>
                                            <span class="flex items-center gap-2">
                                                <span class="w-4 text-xs font-semibold uppercase {{ $isCurrent ? 'text-primary' : 'text-base-content/45' }}">
                                                    {{ str($code)->upper() }}
                                                </span>
                                                {{ $code === 'id' ? __('Indonesian') : __('English') }}
                                            </span>
                                            @if ($isCurrent)
                                                <x-icon name="success" :size="14" class="text-primary" />
                                            @endif
                                        </button>
                                    </form>
                                </li>
                            @endforeach

                            <li class="mt-1 border-t border-base-300 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="text-error">
                                        <x-icon name="logout" :size="16" />
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-3 py-4 sm:px-5 sm:py-6">
            @if (session('status'))
                <div role="status" class="alert alert-success alert-soft mb-4 py-2 text-sm">{{ session('status') }}</div>
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

        <footer class="border-t border-base-300 px-5 py-3 text-[11px] text-base-content/45">
            FARM &middot; Farm Management System &middot; {{ now()->translatedFormat('d M Y') }}
        </footer>
    </div>

    <aside class="drawer-side z-40">
        <label for="app-drawer" aria-label="{{ __('Close menu') }}" class="drawer-overlay"></label>

        <div class="flex min-h-full w-72 flex-col border-r border-base-300 bg-base-100">
            <div class="flex h-14 items-center gap-2.5 border-b border-base-300 px-4">
                <span class="grid size-8 place-items-center rounded-field bg-primary text-sm font-bold text-primary-content">F</span>
                <span class="leading-tight">
                    <span class="block text-sm font-bold tracking-wide">FARM</span>
                    <span class="block text-[10px] tracking-wide text-base-content/50">FARM MANAGEMENT SYSTEM</span>
                </span>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-3" aria-label="{{ __('Dashboard') }}">
                <ul class="menu menu-md w-full gap-0.5 p-0 text-[13px]">
                    @foreach (\App\Support\Navigation::forUser(auth()->user()) as $group)
                        <li class="menu-title mt-2 px-3 text-[10px] uppercase tracking-[0.12em] first:mt-0">
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

            <div class="border-t border-base-300 px-4 py-3 text-[11px] text-base-content/45">
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
