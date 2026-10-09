---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## DaisyUI modifiers only, never hand-rolled status classes
Reuse the existing components (`x-panel`, `x-stat`, `x-badge`, `x-alert-row`, `x-empty-state`, `x-page-header`, `x-section-title`, `x-icon`, `x-form.*`) instead of hand-rolling markup; they sit on DaisyUI primitives while keeping their old names so existing call sites work. Status colours come from `Enum::badgeClass()` and must be real DaisyUI modifiers (`badge-soft badge-success`) — never composites like `badge-error/15 text-error border-error/25`. Use `table table-sm table-zebra` (no `.table-compact`), `<progress>` for meters, and buttons written variant-then-size (`btn btn-primary btn-sm`). Never add `input-bordered`/`select-bordered`/`textarea-bordered`: DaisyUI 5 removed them. Get icons from `<x-icon>`; there is no icon dependency.
