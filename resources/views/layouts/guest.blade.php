<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? __('Log in') }} &middot; FARM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark-black.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-mark-black.png') }}">
    <x-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-base-200 font-sans">
<div class="flex min-h-dvh items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center gap-3">
            <span class="logo-mark size-10 shrink-0" role="img" aria-label="FARM"></span>
            <span class="min-w-0 leading-tight">
                <span class="block text-lg font-bold tracking-wide">FARM</span>
                <span class="text-label block truncate text-base-content/50">{{ __('Farm Management System') }}</span>
            </span>
        </div>

        <div class="card border border-base-300 bg-base-100">
            <div class="card-body gap-4">
                <div>
                    <h1 class="text-lg font-semibold">{{ $title ?? __('Log in to FARM') }}</h1>
                    @isset($subtitle)
                        <p class="mt-1 text-sm text-base-content/60">{{ $subtitle }}</p>
                    @endisset
                </div>

                @if ($errors->any())
                    <div role="alert" class="alert alert-error alert-soft py-2 text-sm">
                        <x-icon name="error" :size="16" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <x-theme-switcher variant="toggle" />
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>