---
paths:
  - 'app/Models/PatientAttachment.php,app/Models/Patient.php,resources/views/pages/patients/⚡show.blade.php,routes/patients.php,database/migrations/*patient_attachments*'
---

# Patients Migrations

## Patient attachments (referrals/prescriptions/imaging)
`PatientAttachment` (table `patient_attachments`) lets staff upload referrals/prescriptions/imaging/other files to a patient's chart from `patients/⚡show.blade.php` — first use of Livewire's `WithFileUploads` in this codebase. Files validated `mimes:pdf,jpg,jpeg,png|max:10240`, stored on the `local` disk under `attachments/{patient_id}/`. Upload is open to any user who can view the patient (matches note-creation's permission level); delete requires `patients.manage` (mirrors note deletion's gating). Downloads go through `attachments/{attachment}/download` (routes/patients.php) which logs an explicit `activity('patient')->log('attachment downloaded')` — HIPAA audit-log requirement for "attachment uploaded/downloaded", not covered by LogsActivity's automatic create/update/delete hooks. See [[hipaa-mfa-admin-gate]] and [[note-addendum-instead-of-edit]] for the sibling compliance work this was added alongside.
