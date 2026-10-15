---
paths:
  - resources/views/components/stat.blade.php
---

# Components

## KPI tiles come from x-stat, not ad-hoc cards
Tiles use the shared component so label/value/hint spacing and tone stay consistent. Never write a bare `.card` with a `.stat-value` by hand. On phones the tile must stay compact (label + value + hint in three lines, no icon tile) — six tiles at desktop padding pushed the real data below the fold on every list page.
