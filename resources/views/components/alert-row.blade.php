@props(['tone' => 'info', 'title', 'description' => null, 'url' => null, 'urlLabel' => null])

@php
    $tone = in_array($tone, ['info', 'success', 'warning', 'error'], true) ? $tone : 'info';
    $icon = match ($tone) {
        'error' => 'error',
        'warning' => 'warning',
        'success' => 'success',
        default => 'info',
    };
@endphp

<div {{ $attributes->class(['alert alert-soft alert-'.$tone, 'items-start', 'px-3 py-2.5', 'text-sm']) }}>
    <x-icon :name="$icon" :size="16" class="mt-0.5" />
    <div class="min-w-0 flex-1">
        <p class="font-medium leading-snug">{{ $title }}</p>
        @if ($description)<p class="mt-0.5 text-xs opacity-70">{{ $description }}</p>@endif
    </div>
    @if ($url)
        <a href="{{ $url }}" class="btn btn-ghost btn-xs -my-1 shrink-0 px-2">{{ $urlLabel ?? __('View') }}</a>
    @endif
</div>
