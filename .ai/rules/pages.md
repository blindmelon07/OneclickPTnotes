---
paths:
  - 'app/Models/PatientColor.php,resources/views/pages/⚡calendar.blade.php'
---

# Pages

## Per-patient calendar colors are personal, keyed by (user_id, patient_id)
`patient_colors` (unique on `user_id`+`patient_id`, cascade-deletes with either) overrides a viewer's `users.calendar_color` default for one specific patient — see [[calendar-accent-color-is-a-per-user-tailwind-palette-key]]. It's set via `setPatientColor()` (`PatientColor::updateOrCreate`), read via the `patientColors` computed + `colorForPatient(int $patientId): string` (override ?? the viewer's default ?? `'red'`), and — like `calendar_color` — not activity-logged (personal UI preference, not a clinical/business record). `User::$attributes = ['calendar_color' => 'red']` mirrors the DB column default so a freshly-created-but-unrefreshed `User` instance (e.g. `User::factory()->create()` in a test, before any re-fetch) doesn't read as `null` — found because `colorForPatient()`'s `: string` return type turned that gap into a hard `TypeError` in a test.
