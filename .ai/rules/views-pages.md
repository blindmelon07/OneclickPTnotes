---
paths:
  - resources/views/pages/⚡calendar.blade.php
---

# Views Pages

## Calendar's patient-colors legend is paginated
`patients()` returns a `LengthAwarePaginator` (`Patient::whereIn('id', ...)->paginate(5)`, `PATIENTS_PER_PAGE`), not a plain Collection — rendered with `<flux:pagination :paginator="$this->patients" />` (Flux's standalone pagination component, no `<flux:table>` needed). The component uses `WithPagination`; `previousMonth()`/`nextMonth()`/`goToToday()` all call `resetPage()` after changing `$this->month`, since a page number valid for one month's patient list can easily be out of range for another's.
