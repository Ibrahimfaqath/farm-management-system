<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? __('Document') }} &middot; FARM</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark-black.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-mark-black.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="document-page min-h-screen bg-base-300 font-sans text-slate-900 antialiased print:bg-white">
    <div class="document-toolbar sticky top-0 z-10 border-b border-base-300 bg-base-100 print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center justify-between gap-2 px-3 py-2 sm:px-5">
            <a href="{{ $back ?? url()->previous() }}" class="btn btn-ghost btn-sm">
                <x-icon name="back" :size="16" />
                {{ __('Back') }}
            </a>

            <p class="order-first w-full text-center text-xs font-medium text-base-content/55 sm:order-none sm:w-auto">
                {{ $title ?? __('Document') }}
            </p>

            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <x-icon name="print" :size="16" />
                {{ __('Print') }}
            </button>
        </div>
    </div>

    <main class="px-3 py-4 sm:px-6 sm:py-8 print:p-0">
        @yield('content')
    </main>
</body>
</html>
