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
     * control, two or more keep the dropdown. Nothing at the call sites has to
     * change, and a page whose actions are conditional settles on whichever
     * shape it actually ended up with.
     *
     * A direct control keeps its icon and shows only that icon. The text stays
     * in the markup for assistive technology and is copied to `aria-label` and
     * `title`, so the control is still named and still explains itself on
     * hover without spending a full label's width in the action column.
     */
    $content = trim((string) $slot);
    $entryCount = preg_match_all('/<li\b/', $content);

    $single = null;

    if ($entryCount === 1 && preg_match('/^<li\b[^>]*>(.*)<\/li>$/s', $content, $matches)) {
        // Keep whatever the entry was — a link or a POST form — so the control
        // still navigates and still submits. The glyph stays; the visible label
        // is hidden by CSS only when there is a glyph to take its place.
        $single = trim($matches[1]);

        $labelText = trim(preg_replace('/\s+/', ' ', strip_tags($single)));

        if ($labelText !== '') {
            $single = preg_replace_callback('/<(a|button)\b([^>]*)>/i', function (array $tagMatches) use ($labelText): string {
                if (str_contains($tagMatches[2], 'aria-label=')) {
                    return $tagMatches[0];
                }

                return '<'.$tagMatches[1].$tagMatches[2].' aria-label="'.e($labelText).'" title="'.e($labelText).'">';
            }, $single, 1);
        }
    }
@endphp

@if ($single !== null)
    <div {{ $attributes->class(['row-single-action', 'has-icon' => str_contains($single, '<svg')]) }}>
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