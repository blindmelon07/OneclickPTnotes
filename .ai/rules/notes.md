---
paths:
  - 'app/Models/Note.php,resources/views/pages/patients/notes/**,app/Actions/Notes/**,resources/views/pdf/notes/**'
---

# Notes

## Notes require both a staff and a patient signature
Extends [[patient-note-clinical-documentation-workflow]]: a note now needs TWO signatures captured on the same `<x-signature-pad>`-based form before `signAndSend()` will create/send it — staff (`signature_path`/`signed_at`, unchanged) and patient (`patient_signature_path`/`patient_signed_at`, new columns). Both are required; missing either blocks note creation entirely (no partial/draft Note row is persisted) and adds a validation error (`signature` or `patientSignature`).

Patient signature files are stored as `signatures/{note}-patient.png` (staff stays `signatures/{note}.png`), served via the `notes.patient-signature` route (mirrors `notes.signature`), and embedded in the PDF by `GenerateNotePdf` via the same `signatureDataUri()`-style helper for both. `Note::isPatientSigned()` mirrors `isSigned()`. `NoteFactory::signed()` sets both pairs together — there's no factory state for "staff signed only."
