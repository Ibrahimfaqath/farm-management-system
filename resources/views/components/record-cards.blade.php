@props(['label' => null])

{{--
    The phone presentation of a data table.

    A nine-column table on a 375px screen has two bad options: shrink the type
    until it cannot be read, or scroll sideways through columns that cut off
    mid-word. Measured on the livestock list, the "Breed" column wrapped
    "Holstein Friesian" onto two lines, every row grew to about 100px, and the
    status and barn the reader actually came for sat off-screen behind a
    horizontal scroll with nothing to say it was there.

    So below `lg` the table is replaced by a list of cards, each carrying the
    row's identity, its status, the two or three facts that matter for that
    record, and the same action the row menu offered. The table is kept in the
    DOM for `lg` and above, so no data is dropped at either size — only the
    arrangement changes.

    The cards are not the table with fewer columns; the order is by what someone
    scanning a list on a phone needs first: what this is, which state it is in,
    then the facts, then the action.
--}}
<div {{ $attributes->class(['motion-rows lg:hidden']) }} @if ($label) role="list" aria-label="{{ $label }}" @endif>
    {{ $slot }}
</div>

@once
    <style>
        /*
            Hairline rules between cards, with the gap kept on the container
            rather than on each card so the first and last row align with the
            filter bar and the pagination above and below them.
        */
        @media (width < 64rem) {
            .record-card + .record-card {
                border-top: 1px solid var(--color-base-300);
            }
        }
    </style>
@endonce