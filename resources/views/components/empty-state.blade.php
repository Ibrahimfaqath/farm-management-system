@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null, 'actionUrl' => null, 'icon' => 'inbox'])

<div {{ $attributes->class(['card card-dash border-base-300 bg-base-100 px-6 py-10']) }}>
    <div class="flex flex-col items-center justify-center text-center">
        <div class="grid size-11 place-items-center rounded-full bg-base-200 text-base-content/35">
            <x-icon :name="$icon" :size="20" :stroke="1.5" />
        </div>
        <p class="mt-3 text-sm font-medium">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 max-w-md text-xs text-base-content/55">{{ $description }}</p>
        @endif
        @if ($actionLabel && $actionUrl)
            <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm mt-4">{{ $actionLabel }}</a>
        @elseif ($actionLabel)
            <div class="mt-4">{{ $action }}</div>
        @endif
    </div>
</div>
