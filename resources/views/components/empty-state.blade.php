@props([
    'label' => null,
    'code' => null,
    'description' => null,
    'url' => null,
    'submitLabel' => null,
    'title' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

@php
    // `title`/`actionLabel`/`actionUrl` are the original prop names and are kept
    // so the ~30 existing call sites keep working unchanged.
    $label = $label ?? $title;
    $submitLabel = $submitLabel ?? $actionLabel;
    $url = $url ?? $actionUrl;
@endphp

<div {{ $attributes->class(['card card-dash border-base-300 bg-base-100 px-6 py-10']) }}>
    <div class="flex flex-col items-center justify-center text-center">
        <div class="grid size-11 place-items-center rounded-full bg-base-200 text-base-content/35">
            <x-icon name="inbox" :size="20" :stroke="1.5" />
        </div>
        @if ($label)
            <p class="mt-3 text-sm font-medium">{{ $label }}</p>
        @endif
        @if ($code)
            <p class="text-meta mt-0.5 font-mono text-base-content/45">{{ $code }}</p>
        @endif
        @if ($description)
            <p class="mt-1 max-w-md text-xs text-base-content/55">{{ $description }}</p>
        @endif

        @isset($action)
            <div class="mt-4">{{ $action }}</div>
        @elseif ($url && $submitLabel)
            <a href="{{ $url }}" class="btn btn-primary btn-sm mt-4">{{ $submitLabel }}</a>
        @endif
    </div>
</div>