@props(['title', 'subtitle' => null, 'back' => null, 'icon' => null, 'breadcrumbs' => []])

@php
    $backUrl = is_string($back) ? $back : (is_array($back) ? ($back['url'] ?? null) : null);
@endphp

<header class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        @if ($breadcrumbs)
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-1">
                <ol class="breadcrumbs breadcrumbs-sm p-0 text-xs">
                    @foreach ($breadcrumbs as $crumb)
                        <li>
                            @if (! $loop->last && ! empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="link link-hover">{{ $crumb['label'] }}</a>
                            @else
                                <span aria-current="{{ $loop->last ? 'page' : 'false' }}">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="flex items-center gap-2">
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-ghost btn-xs btn-square -ml-1" aria-label="{{ __('Back') }}">
                    <x-icon name="back" :size="16" :stroke="1.8" />
                </a>
            @endif

            @if ($icon)
                <span class="grid size-8 shrink-0 place-items-center rounded-field bg-primary/10 text-primary">
                    <x-icon :name="$icon" :size="17" />
                </span>
            @endif

            <h2 class="page-title">{{ $title }}</h2>
        </div>

        @if ($subtitle)
            <p class="page-subtitle mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2 print:hidden">{{ $actions }}</div>
    @endisset
</header>