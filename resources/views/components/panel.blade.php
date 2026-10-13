@props(['title' => null, 'description' => null, 'padding' => 'p-4', 'body' => false])

{{--
    The single bordered container for the whole app.

    It is built on DaisyUI's `card` primitive so the radius, background and
    focus contract come from the theme, while the body stays a plain padded
    box. That keeps the padding rhythm predictable for the many dense panels
    (tables, forms, key-value lists) that would otherwise pick up
    `card-body`'s flex gap.
--}}
<div {{ $attributes->class(['card min-w-0 border border-base-300 bg-base-100']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-base-300 px-4 py-2.5">
            <div class="min-w-0">
                @if ($title)<h3 class="truncate text-sm font-semibold leading-tight">{{ $title }}</h3>@endif
                @if ($description)<p class="mt-0.5 text-xs text-base-content/55">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif

    @if ($body)
        <div class="card-body {{ $padding }}">{{ $slot }}</div>
    @else
        <div class="{{ $padding }}">{{ $slot }}</div>
    @endif

    @isset($footer)
        <footer class="border-t border-base-300 px-4 py-3">{{ $footer }}</footer>
    @endif
</div>