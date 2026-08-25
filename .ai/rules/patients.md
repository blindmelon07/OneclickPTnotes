---
paths:
  - 'app/Models/Patient.php,resources/views/pages/patients/**'
---

# Patients

## Patient → PT Assistant assignment
`patients.pt_assistant_id` (nullable FK → users, nullOnDelete) is a persistent "who's responsible for this patient's care" assignment, separate from `visits.therapist_id` (who actually performed a given visit — see [[invoices]]'s eligibility query, which still keys off `therapist_id`, not this field). The dropdown in both the index create-modal and show edit-modal is scoped with Spatie's `User::role('PT Assistant')` (not all users) via a `ptAssistants()` computed property. `Patient::ptAssistant(): BelongsTo` mirrors `doctor()`/`homeHealthAgency()`.
