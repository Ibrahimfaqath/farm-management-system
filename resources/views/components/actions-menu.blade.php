@props([
    'label' => null,
    'align' => 'dropdown-end',
    'offset' => 'mt-1',
    'width' => 'w-52',
])

@php
    /*
     * A row's actions have to earn a menu.
     *
     * Most rows here offer exactly one action — Detail on eight index pages,
     * Edit on four — and hiding a single action behind an overflow button makes
     * the common case cost two clicks to reach the row's primary action. The
     * reason the menu exists at all is geometric: a row with two buttons
     * stretched the row and pushed the table wider. One button does neither.
     *
     * So the component reads its own slot: a single entry renders as a direct
     * control, two or more keep the dropdown. Nothing at the 111 call sites has
     * to change, and a page whose actions are conditional settles on whichever
     * shape it actually ended up with.
     */
    $content = trim((string) $slot);
    $entryCount = preg_match_all('/<li\b/', $content);

    $single = null;

    if ($entryCount === 1 && preg_match('/^<li\b[^>]*>(.*)<\/li>$/s', $content, $matches)) {
        // Keep whatever the entry was — a link or a POST form — so the control
        // still navigates and still submits. The glyph goes: it cost about 20px
        // per row and a single word already names the action unambiguously.
        $single = trim(preg_replace('/<svg\b.*?<\/svg>/s', '', $matches[1]));
    }
@endphp

@if ($single !== null)
    <div {{ $attributes->class(['row-single-action']) }}>
        {!! $single !!}
    </div>
@else
    <div {{ $attributes->class(['dropdown', $align, 'row-menu']) }}>
        <button type="button" tabindex="0"
                class="btn btn-ghost btn-xs btn-square row-menu-trigger"
                aria-label="{{ $label ?? __('Actions') }}"
                aria-haspopup="true" aria-expanded="false">
            <x-icon name="more" :size="16" />
        </button>

        <ul tabindex="0" role="menu"
            class="menu dropdown-content z-50 {{ $offset }} {{ $width }} flex-nowrap rounded-box border border-base-300 bg-base-100 p-1 shadow-lg">
            {!! $content !!}
        </ul>
    </div>
@endif