{{--
    Form action row.

    `bare` drops the divider for the cases where the row already sits inside a
    panel; `boxed` turns it into its own surface for pages that need the action
    to read as a separate step.
--}}
@props(['bare' => false, 'boxed' => false])

<div {{ $attributes->class([
        'flex flex-wrap items-center justify-end gap-2 print:hidden',
        'border-t border-base-300 pt-4' => ! $bare && ! $boxed,
        'rounded-box border border-base-300 bg-base-100 px-4 py-3' => $boxed,
    ]) }}>
    {{ $slot }}
</div>