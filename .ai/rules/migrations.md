---
paths:
  - 'app/Models/Note.php,app/Models/NoteAddendum.php,resources/views/pages/patients/notes/**,database/migrations/*note_addenda*'
---

# Migrations

## Signed notes are never edited or deleted — use NoteAddendum
A signed Note (`isSigned()`) can no longer be deleted — `notes/⚡show.blade.php::delete()` now `abort_if($note->isSigned(), 403)` even for users with `notes.manage`. There is still no edit route for notes at all (unchanged). Corrections to a signed note go through `NoteAddendum` (table `note_addenda` — model sets `$table` explicitly since Eloquent can't pluralize "addendum"): immutable, timestamped, tied to `author_id`, logged via LogsActivity under log_name `note`. `Note::addenda()` is a `HasMany` ordered oldest-first. Addenda are NOT merged into the original signed PDF (`GenerateNotePdf` untouched) — they only show on the notes/show page and audit log; the original signed document stays exactly as generated. This exists to satisfy HIPAA's "don't silently alter/destroy ePHI" requirement — see [[hipaa-mfa-admin-gate]] for the sibling compliance change.
