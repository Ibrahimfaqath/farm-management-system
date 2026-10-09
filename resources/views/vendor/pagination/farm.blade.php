@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-base-content/60">
            {{ __('Showing') }}
            <span class="font-medium text-base-content">{{ $paginator->firstItem() }}</span>
            {{ __('to') }}
            <span class="font-medium text-base-content">{{ $paginator->lastItem() }}</span>
            {{ __('of') }}
            <span class="font-medium text-base-content">{{ $paginator->total() }}</span>
            {{ __('results') }}
        </p>

        <div class="join">
            @if ($paginator->onFirstPage())
                <button type="button" class="btn btn-sm join-item btn-disabled" aria-disabled="true" tabindex="-1">
                    <x-icon name="back" :size="16" />
                    <span class="hidden sm:inline">{{ __('Previous') }}</span>
                </button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm join-item">
                    <x-icon name="back" :size="16" />
                    <span class="hidden sm:inline">{{ __('Previous') }}</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <button type="button" class="btn btn-sm join-item btn-disabled" aria-disabled="true" tabindex="-1">
                        {{ $element }}
                    </button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button type="button" class="btn btn-sm join-item" aria-current="page">
                                <span class="size-1.5 rounded-full bg-current"></span>
                                {{ $page }}
                            </button>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm join-item" aria-label="{{ __('Go to page') }} {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm join-item">
                    <span class="hidden sm:inline">{{ __('Next') }}</span>
                    <x-icon name="forward" :size="16" />
                </a>
            @else
                <button type="button" class="btn btn-sm join-item btn-disabled" aria-disabled="true" tabindex="-1">
                    <span class="hidden sm:inline">{{ __('Next') }}</span>
                    <x-icon name="forward" :size="16" />
                </button>
            @endif
        </div>
    </nav>
@endif
