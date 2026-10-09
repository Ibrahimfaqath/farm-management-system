@props(['title', 'subtitle' => null, 'back' => null, 'icon' => null])

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="flex min-w-0 items-start gap-3">
        @if ($icon)
            <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-field bg-primary/10 text-primary">
                <x-icon :name="$icon" :size="18" />
            </span>
        @endif
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                @if ($back)
                    <a href="{{ $back }}" class="btn btn-ghost btn-xs btn-square -ml-1" aria-label="{{ __('Back') }}">
                        <x-icon name="back" :size="16" :stroke="1.8" />
                    </a>
                @endif
                <h2 class="page-title">{{ $title }}</h2>
            </div>
            @if ($subtitle)
                <p class="page-subtitle mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
