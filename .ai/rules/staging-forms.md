---
paths:
  - 'app/Models/StagingForm.php,resources/views/pages/staging-forms/**,resources/views/pdf/staging-forms/**,routes/staging-forms.php'
---

# Staging Forms

## Staging Forms mirror the client's IE spreadsheet (yellow = input, green = fixed)
Staging Forms port the client's "Copy for one click PT notes HOME HEALTH IE - GENERIC FORM.xlsx". Yellow cells = the only VA-editable inputs (the evaluation form, stored as JSON in `staging_forms.data`); the Visit Note, Treatment Order, PTA Communication and Team Conference PDFs copy from it. Green cells = fixed content (`StagingForm::fixed*()` lists, PTA total = sum of weekly visits) and must never become editable. Deliberately standalone — not linked to Patient/Note — so the client can review it before it's merged into the real notes workflow. PDFs render on demand (not stored); gated by `notes.manage`. DomPDF's Helvetica can't print "—", so use "-" in PDF body text.
