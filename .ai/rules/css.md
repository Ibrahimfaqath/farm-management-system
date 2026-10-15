---
paths:
  - resources/css/app.css
---

# Css

## Surface and elevation tokens are the only way to add depth
Flatness came from every surface being `bg-base-100` + a 1px border with no elevation. Depth now comes from the `--surface-*` and `--shadow-*` tokens plus the `.surface-raised` / `.surface-sunken` helpers; do not hand-write rgba shadows or per-view greys. Warm the neutrals slightly with `--color-base-*` overrides so the interface is not clinical grey.
