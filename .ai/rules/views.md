---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## DaisyUI modifiers only, never hand-rolled status classes
Reuse the existing components (`x-panel`, `x-stat`, `x-badge`, `x-alert-row`, `x-empty-state`, `x-page-header`, `x-section-title`, `x-icon`, `x-form.*`) instead of hand-rolling markup; they sit on DaisyUI primitives while keeping their old names so existing call sites work. Status colours come from `Enum::badgeClass()` and must be real DaisyUI modifiers (`badge-soft badge-success`) — never composites like `badge-error/15 text-error border-error/25`. Use `table table-sm table-zebra` (no `.table-compact`), `<progress>` for meters, and buttons written variant-then-size (`btn btn-primary btn-sm`). Never add `input-bordered`/`select-bordered`/`textarea-bordered`: DaisyUI 5 removed them. Get icons from `<x-icon>`; there is no icon dependency.

## Mobile content order follows work priority, not desktop order
On a phone the operational summary comes before the long alert list. Use `order-*` utilities to reorder blocks for narrow viewports rather than duplicating markup. Never hide a figure on mobile to save space; move it below.

## Wide tables get a mobile card list or an explicit scroll cue
A nine-column table on a 375px screen either becomes a card list carrying the row's identity, status and primary action, or keeps scrolling with a visible edge shadow. Never shrink the type to fit, and never hide a column without a route to the full record.
