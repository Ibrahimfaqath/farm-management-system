@props(['title', 'subtitle' => null, 'back' => null, 'icon' => null, 'breadcrumbs' => []])

@php
    $backUrl = is_string($back) ? $back : (is_array($back) ? ($back['url'] ?? null) : null);
@endphp

{{--
    The heading for a page: title, a line of context, and the primary action.

    Three decisions here are about a phone rather than a desk.

    The whole block sits above the action on a narrow screen, so the action is
    the first thing the thumb lands on rather than something off to the side of
    a wrapped title.

    The title steps down one size below `lg`. At 24px a heading like "What needs
    attention today" wrapped onto two lines and, with its subtitle, took close
    to 280px of a 375px screen before any content — which is a quarter of the
    viewport spent restating where you are.

    The icon tile is dropped on a phone for the same reason: it is a second
    piece of chrome above the fold that says nothing the title does not.
--}}
<header {{ $attributes->class(['mb-4 flex flex-col gap-3 sm:mb-5 lg:flex-row lg:flex-wrap lg:items-start lg:justify-between lg:gap-3']) }}>
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
                <span class="hidden size-8 shrink-0 place-items-center rounded-field bg-primary/10 text-primary sm:grid">
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