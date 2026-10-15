@props(['title', 'count' => null, 'icon' => null])

{{--
    The heading for a block of content.

    The trailing rule is the part that does the work. A bare uppercase label
    floating above a panel leaves the eye with nothing to follow, so the hairline
    carries the eye across to where the content begins — which matters most on
    a dashboard, where four of these sit in a column and each one needs to read
    as the start of something rather than as a stray caption.

    Attributes are merged in the same way `x-panel` and `x-stat` do, so a caller
    can adjust the margin or hide the heading at a breakpoint without the
    component having to grow a prop for it.
--}}
<div {{ $attributes->class(['mb-3 flex items-center gap-2.5']) }}>
    @if ($icon)
        <span class="shrink-0 text-base-content/35 [&_svg]:size-4"><x-icon :name="$icon" :size="16" /></span>
    @endif

    <h3 class="shrink-0 text-xs font-semibold uppercase tracking-wide text-base-content/65">{{ $title }}</h3>

    @if ($count !== null)
        <span class="badge badge-ghost badge-sm shrink-0 tabular-nums">{{ $count }}</span>
    @endif

    <span aria-hidden="true" class="h-px flex-1 bg-gradient-to-r from-base-300 to-transparent"></span>
</div>