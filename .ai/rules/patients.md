---
paths:
  - 'app/Models/Patient.php,resources/views/pages/patients/**'
---

# Patients

## Patient → PT Assistant assignment
`patients.pt_assistant_id` (nullable FK → users, nullOnDelete) is a persistent "who's responsible for this patient's care" assignment, separate from `visits.therapist_id` (who actually performed a given visit — see [[invoices]]'s eligibility query, which still keys off `therapist_id`, not this field). The dropdown in both the index create-modal and show edit-modal is scoped with Spatie's `User::role('PT Assistant')` (not all users) via a `ptAssistants()` computed property. `Patient::ptAssistant(): BelongsTo` mirrors `doctor()`/`homeHealthAgency()`.

## Patient statuses come from statusLabels(), never ucfirst()
`patients.status` is a plain string column (no enum), so adding a status means adding a `STATUS_*` const plus an entry in `Patient::statusLabels()` (value => display label) and a `statusColor()` arm — nothing else. `statuses()` is derived from `array_keys(statusLabels())`, so validation (`in:`) and every select pick it up automatically.

Labels are multi-word and irregular ("On-Hold", "Px Passed Away", "HHA/MD ordered to stop HHPT"), so never render a status with `ucfirst($patient->status)`. Use `$patient->statusLabel()` for badges and loop `Patient::statusLabels() as $value => $label` for selects.
