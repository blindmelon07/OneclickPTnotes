---
paths:
  - 'app/Actions/Patients/**,resources/views/pdf/patients/**,routes/patients.php'
---

# Pdf Patients

## Full patient chart export re-renders note data — doesn't merge original PDFs
`GeneratePatientChartPdf` (app/Actions/Patients/) renders every signed Note's data + signatures + addenda, plus an attachments metadata table, into ONE combined PDF via `pdf.patients.chart`. This is a deliberate design choice made to avoid adding a PDF-merge dependency (e.g. setasign/fpdi): it does NOT literally merge the individually-generated, already-signed note PDFs (`Note::pdf_path`) byte-for-byte — it re-renders the same underlying data that fed those PDFs. If a future requirement needs the export to be byte-identical to what was originally signed, that requires adding a PDF-merge library (composer dependency — needs approval) rather than extending this action. Output is stored/overwritten at a stable path (`patients/{id}/chart.pdf`) each time it's requested — not accumulated as timestamped snapshots — since the chart's content changes as new notes are added. Download route (`patients.chart.download`) logs `activity('patient')->log('chart exported')` for the audit trail. See [[patient-attachments]] for the sibling attachments feature added alongside this.
