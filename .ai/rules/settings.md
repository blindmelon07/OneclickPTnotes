---
paths:
  - 'resources/views/pages/admin/layout.blade.php,resources/views/pages/settings/layout.blade.php'
---

# Settings

## These two-column layouts need min-w-0 on the content flex item
Both layouts use `flex items-start max-md:flex-col` with a fixed-width sidebar (`w-[220px]`) and a `flex-1` content column. At exactly the `md` breakpoint (768px, i.e. iPad portrait), `max-md:flex-col` doesn't apply yet, so the row layout is still active — and a `flex-1` child defaults to `min-width: auto`, so a wide child (e.g. a `<flux:table>`, which has its own internal `overflow-auto` scroll area) forces the whole row wider than the viewport, overflowing the page itself even though the table "looks" scrollable. Fix: the content column needs `min-w-0` alongside `flex-1` (already applied). Keep this whenever adding wide content (tables, code blocks) to an admin or settings page.
