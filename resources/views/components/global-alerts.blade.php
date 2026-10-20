@php
    /** @var \Illuminate\Support\Collection<int, array{tone: string, title: string, description?: string, url: string}> $attentionItems */
    $attentionItems = $attentionItems ?? collect();

    // Only the items that actually need a decision. Anything with the `info`
    // tone is a neutral status line, not something to interrupt somebody for,
    // and counting those turned the badge into a number nobody could act on.
    $attention = $attentionItems->filter(fn (array $item) => ($item['tone'] ?? 'info') !== 'info');

    $toneGlyph = [
        'error' => ['icon' => 'error', 'class' => 'text-tone-error', 'badge' => 'badge-error'],
        'warning' => ['icon' => 'warning', 'class' => 'text-tone-warning', 'badge' => 'badge-warning'],
        'info' => ['icon' => 'info', 'class' => 'text-tone-info', 'badge' => 'badge-info'],
    ];
@endphp

{{--
    What needs follow-up.

    The panel is a real DaisyUI `menu` rather than a hand-built list of links.
    It was previously a bare `<ul>` with `hover:bg-base-200` on each row and no
    list semantics, while the account menu beside it used `menu` — two panels,
    two behaviours, from one header. Sharing the component means one row height,
    one focus treatment, one set of hover states.

    Each item also carries a tone glyph. The old rows were four lines of grey
    text with nothing to scan by, so "which of these six is the urgent one" was
    a reading task rather than a looking one.
--}}

<div {{ $attributes->class(['dropdown dropdown-end']) }}>
    <button type="button" tabindex="0"
            class="btn btn-ghost btn-sm btn-square size-10"
            aria-label="{{ $attention->isNotEmpty() ? __('Items needing attention') : __('No items need attention') }}"
            aria-haspopup="true" aria-expanded="false">
        <x-icon name="bell" :size="19" />

        @if ($attention->isNotEmpty())
            {{-- Anchored to the glyph rather than to the button, so a count that
                 grows to two digits pushes outward instead of clipping against
                 the button's own edge. --}}
            <span class="text-meta badge badge-error badge-xs notification-badge absolute -right-0.5 -top-0.5 border-2 border-base-100 px-1 leading-4">
                {{ $attention->count() }}
            </span>
            <span class="sr-only">{{ $attention->count() }} {{ __('items need attention') }}</span>
        @endif
    </button>

    {{--
        `dropdown-content` carries no display utility of its own.

        DaisyUI hides a closed panel with `display: none` on this element. A
        `flex` utility here sits in the utilities layer, which is emitted after
        the component layer, so it won: the panel stayed laid out at
        `opacity: 0` and went on covering the page. Clicking anywhere inside its
        invisible box opened it — the bell looked like the whole header was a
        trigger. The layout that was wanted now lives on an inner element.
    --}}
    <div tabindex="0" role="menu"
         class="dropdown-content z-50 mt-2 w-80 max-w-[calc(100vw-1.5rem)] rounded-box border border-base-300 bg-base-100 shadow-lg">
        <div class="flex max-h-[70vh] flex-col overflow-hidden rounded-box">

        <div class="flex flex-none items-center justify-between gap-2 border-b border-base-300 px-3 py-2">
            <span class="text-xs font-semibold">{{ __('Needs attention') }}</span>

            @if ($attention->isNotEmpty())
                <span class="badge badge-error badge-sm">{{ $attention->count() }}</span>
            @endif
        </div>

        @if ($attentionItems->isEmpty())
            {{-- Said in a shape rather than a line. An empty panel with one line
                 of grey text in it reads as something failed to load. Padding is
                 kept tight: this is a status, not a page. --}}
            <div class="flex flex-1 flex-col items-center gap-1.5 px-6 py-5 text-center">
                <span class="grid size-9 place-items-center rounded-full bg-base-200 text-base-content/30">
                    <x-icon name="check" :size="18" />
                </span>
                <span class="text-sm font-medium">{{ __('All clear') }}</span>
                <span class="text-xs text-base-content/55">{{ __('Nothing needs follow-up right now.') }}</span>
            </div>
        @else
            <ul class="menu min-h-0 flex-1 flex-nowrap gap-0.5 overflow-y-auto p-1.5 text-sm">
                @foreach ($attentionItems as $item)
                    @php
                        $tone = $toneGlyph[$item['tone'] ?? 'info'] ?? $toneGlyph['info'];
                    @endphp
                    <li>
                        <a href="{{ $item['url'] }}" class="flex-nowrap items-start gap-2.5 rounded-field py-2">
                            <x-icon :name="$tone['icon']" :size="16" data-line-tone-glyph="{{ $item['tone'] ?? 'info' }}"
                                       class="mt-0.5 shrink-0 {{ $tone['class'] }}" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs leading-snug font-medium">{{ $item['title'] }}</span>
                                @if (! empty($item['description']))
                                    <span class="text-meta block leading-snug text-base-content/55">{{ $item['description'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
        </div>
    </div>
</div>