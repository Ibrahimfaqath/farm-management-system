@props(['title', 'count' => null, 'icon' => null])

<div class="mb-3 flex items-center gap-2">
    @if ($icon)
        <span class="text-base-content/35 [&_svg]:size-4"><x-icon :name="$icon" :size="16" /></span>
    @endif
    <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50">{{ $title }}</h3>
    @if ($count !== null)
        <span class="badge badge-ghost badge-sm tabular-nums">{{ $count }}</span>
    @endif
</div>
