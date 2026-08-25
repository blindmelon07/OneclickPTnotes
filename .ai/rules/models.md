---
paths:
  - 'resources/views/pages/⚡calendar.blade.php,app/Models/Visit.php'
  - 'resources/views/pages/⚡calendar.blade.php,app/Models/User.php'
---

# Models

## Calendar page shows only the viewer's own visits
`/calendar` (`pages::calendar`, route name `calendar`) is a Monday-start month-grid Livewire page, open to any authenticated+verified user (no permission gate, same as the dashboard's "My activity" cards) — it always scopes to `Visit::where('therapist_id', auth()->id())`, never to `patients.pt_assistant_id` (see [[patients]]). `month` is a plain `'Y-m'` public property; `previousMonth()`/`nextMonth()`/`goToToday()` just reassign it, and the `cursor`/`weeks` computed properties re-derive the grid + that month's visits (grouped by `Y-m-d`) from it each request. Each visit links to `patients.show` — there's no separate visit-detail page.

## Calendar accent color is a per-user Tailwind palette key
`users.calendar_color` (string, default `'red'`, in `User`'s `#[Fillable]`) stores a palette key from `⚡calendar.blade.php`'s `Calendar::COLORS` — never a raw hex/CSS value. The component's `chipClasses()`/`todayClasses()`/`swatchClasses()` map each key to a **fully literal** Tailwind class string via `match()` (never `"bg-{$color}-50"` interpolation) — Tailwind's build-time scanner only picks up classes that appear as literal text in a source file, so an interpolated class name would silently produce no CSS. Adding a color means adding it to `COLORS` and to all three `match()` arms. `setColor()` validates against `COLORS` (`abort_unless(..., 422)`) before persisting — it's a public Livewire action reachable with any string.
