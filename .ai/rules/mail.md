---
paths:
  - 'app/Models/{Patient,Visit,Note,Doctor,InsuranceCompany,HomeHealthAgency}.php,resources/views/pages/patients/**,app/Actions/Notes/**,app/Mail/NoteReadyForOffice.php'
---

# Mail

## Patient/Note clinical documentation workflow
Core clinical domain: Patient belongsTo Doctor/InsuranceCompany/HomeHealthAgency; hasMany Visit and Note. Note.type is one of Note::TYPE_IE/RE/DC/FU; Note.data is a flat JSON array keyed by Note::dataFieldLabels(). New note forms are prefilled from prior notes (RE←IE, DC←IE+RE, FU←latest) — see the `prefill()` method in `resources/views/pages/patients/notes/⚡create.blade.php`.

Signing flow: staff draw a signature on `<x-signature-pad model="signature" />` (`resources/views/components/signature-pad.blade.php`, plain Alpine+canvas, no JS package), which does `$wire.set()` with a base64 PNG data URL. On submit the component decodes it to `storage/app/private/signatures/{note}.png`, renders the PDF via `App\Actions\Notes\GenerateNotePdf` (barryvdh/laravel-dompdf, embeds the signature as base64 in `resources/views/pdf/notes/note.blade.php`), stores it under `storage/app/private/notes/`, then emails it synchronously (not queued — must not depend on a queue worker) via `App\Mail\NoteReadyForOffice` to `config('notes.office_email')`.

Patients/Notes are viewable by any authenticated+verified user; only the `patients.manage`/`notes.manage` Spatie permissions gate create/edit/delete of records (signing/emailing a note itself is not permission-gated — every staff member can do that). See [[rbac-via-spatie-laravel-permission]].
