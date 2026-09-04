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

## date_of_re_not_applicable distinguishes "no RE" from "RE not done yet"
Not every patient gets a re-evaluation, so a null `date_of_re` is ambiguous. `patients.date_of_re_not_applicable` (boolean, default false) records the "N/A" case explicitly, surfaced as a checkbox beside the Date of RE input in the edit modal on `⚡show.blade.php`.

The two can never both be set: `updatedDateOfReNotApplicable()` clears the date when the box is ticked, and `prohibited_if:date_of_re_not_applicable,true` on `date_of_re` backstops it. Keep the checkbox on `wire:model.live` — the clearing hook and the input's `:disabled` both depend on the live round trip.

## pta_visits is derived from approved_visits, not entered from scratch
The admin performs the evaluation and the discharge personally, so a course of 7 approved visits leaves the PT Assistant 5. `Patient::ptaVisitsForApprovedVisits()` is that arithmetic (`max(0, approved - ADMIN_VISITS_PER_COURSE)`, null in / null out) and must stay in step with `therapistForVisitNumber()`, which is what actually hands out visit 1 and the final visit — change one and change the other.

It fills `pta_visits` in two places: `updatedApprovedVisits()` on `⚡show.blade.php` (hence `wire:model.live` on that input) and `createPatient()` on `⚡index.blade.php`, where there is no PTA input at all. The field stays editable afterwards for a course that splits differently; only a later change to the approved total overwrites it.
