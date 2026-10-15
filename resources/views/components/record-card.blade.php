@props(['href' => null, 'title' => null])

{{--
    One record in a phone list.

    The whole card is a tap target when there is a `href`, rather than only the
    code being a link: on a touch screen a 19px link inside a dense list is a
    poor aim, while the card around it is already the thing someone would press.
    The action sits above that link on its own stacking layer so it stays
    independently clickable.
--}}
<div {{ $attributes->class(['record-card relative px-3 py-3 transition-colors duration-150 hover:bg-base-200/50']) }}
     @if ($href) role="listitem" @endif>
    @if ($href)
        <a href="{{ $href }}" class="absolute inset-0 z-0" aria-label="{{ $title ?? __('Open record') }}"><span class="sr-only">{{ $title }}</span></a>
    @endif

    {{-- Aligned to the top, so the action sits on the row it belongs to
         rather than floating beside whatever line happens to be the middle
         one. --}}
    <div class="relative flex items-start gap-2 {{ $href ? 'pointer-events-none' : '' }}">
        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>

        @isset($actions)
            <div class="pointer-events-auto relative z-10 shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</div>