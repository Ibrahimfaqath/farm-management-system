@props(['paginator'])

{{--
    Pagination for a list.

    The wrapper — the rule above it and the padding — is the part that matters.
    Written inline as a bare `border-t` div with padding, it still took up its
    own height on a result set that fit on one page, because the links rendered
    nothing and the box did not know that. On the barns list that left an empty
    strip about 40px tall hanging under the last row, inside the panel, looking
    like a half-drawn row.

    `hasPages()` is the test: a single page of results needs no control, and
    nothing to say there is more.
--}}
@if ($paginator->hasPages())
    {{-- A plain div, not a nav. The paginator view already renders
         `<nav aria-label="Pagination Navigation">` around the controls, so
         wrapping it in a second landmark gave the page two navigation regions
         where one is meant. This element only supplies the rule and the
         breathing room. --}}
    <div class="border-t border-base-300 p-3">
        {{ $paginator->links() }}
    </div>
@endif