@php
    $tone = in_array($tone, ['info', 'success', 'warning', 'error'], true) ? $tone : 'info';
    $icon = match ($tone) {
        'error' => 'error',
        'warning' => 'warning',
        'success' => 'success',
        default => 'info',
    };
@endphp

@props(['tone' => 'info', 'title', 'description' => null, 'url' => null, 'urlLabel' => null])

{{--
    Alert rows carry their tone through a tinted border and icon rather than
    tinted body text: the soft DaisyUI warning text is unreadable on the light
    background, and users read the wording, not the colour.
--}}
<div {{ $attributes->class([
        'alert items-start px-3 py-2.5 text-sm',
        'border-'.$tone.'/35 bg-'.$tone.'/10 text-base-content',
    ]) }}
     @if ($tone === 'error') role="alert" @endif>
    <x-icon :name="$icon" :size="16" class="mt-0.5 text-tone-'.$tone.'" />
    <div class="min-w-0 flex-1">
        <p class="font-medium leading-snug">{{ $title }}</p>
        @if ($description)<p class="mt-0.5 text-xs text-base-content/70">{{ $description }}</p>@endif
    </div>
    @if ($url)
        <a href="{{ $url }}" class="btn btn-ghost btn-xs -my-1 shrink-0 px-2 no-underline">{{ $urlLabel ?? __('View') }}</a>
    @endif
</div>