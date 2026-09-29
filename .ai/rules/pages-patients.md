---
paths:
  - resources/views/pages/patients/⚡show.blade.php
---

# Pages Patients

## Grid columns need min-w-0, header rows need flex-wrap
The two-column `grid gap-6 lg:grid-cols-3` layout is a single implicit column below `lg:`, but grid items still default to `min-width: auto` — a card's content (e.g. the Notes section's 4 "New {type}" buttons next to the "Notes" heading in one `justify-between` row) can force the grid track wider than the viewport on phones. Both `<div class="grid ...">` and its two column children carry `min-w-0`, and the "Notes"/"Upcoming visits" header rows use `flex flex-wrap items-center justify-between gap-2` (not plain `flex ... justify-between`) so the button group drops to its own line instead of overflowing. Keep both when touching this page.

## Patient chart views are audit-logged on every mount
`mount()` logs `activity('patient')->performedOn($patient)->causedBy(auth()->user())->log('chart viewed')` every time the patient show page loads — this is the HIPAA "who looked at this patient's record" control (checklist item, not covered by LogsActivity's automatic create/update/delete hooks, which only fire on mutations). It fires once per full page load/`wire:navigate` (mount only), not on every Livewire AJAX action within an already-open page — that's deliberate, matching "chart viewed" semantics rather than spamming per-interaction. Expect activity_log rows to grow faster than before now that read access is tracked, not just writes.
