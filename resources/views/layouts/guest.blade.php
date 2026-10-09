<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="farm">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Log in') }} &middot; FARM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans">
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-field bg-primary text-lg font-bold text-primary-content">F</span>
            <span class="leading-tight">
                <span class="block text-xl font-bold tracking-wide">FARM</span>
                <span class="block text-[11px] tracking-wide text-base-content/50">FARM MANAGEMENT SYSTEM</span>
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
    </div>
</div>
</body>
</html>
