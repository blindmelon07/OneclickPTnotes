---
paths:
  - resources/views/pages/patients/⚡show.blade.php
---

# Pages Patients

## Grid columns need min-w-0, header rows need flex-wrap
The two-column `grid gap-6 lg:grid-cols-3` layout is a single implicit column below `lg:`, but grid items still default to `min-width: auto` — a card's content (e.g. the Notes section's 4 "New {type}" buttons next to the "Notes" heading in one `justify-between` row) can force the grid track wider than the viewport on phones. Both `<div class="grid ...">` and its two column children carry `min-w-0`, and the "Notes"/"Upcoming visits" header rows use `flex flex-wrap items-center justify-between gap-2` (not plain `flex ... justify-between`) so the button group drops to its own line instead of overflowing. Keep both when touching this page.
