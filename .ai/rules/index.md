# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Models/User.php,app/Providers/AppServiceProvider.php,resources/views/pages/admin/⚡users.blade.php,database/migrations/*disabled_at* | .ai/rules/admin-migrations.md |
| app/Models/*.php,resources/views/pages/admin/⚡audit-log.blade.php,resources/views/pages/admin/** | .ai/rules/admin.md |
| config/backup.php,config/filesystems.php,config/database.php,routes/console.php | .ai/rules/config.md |
| app/Mail/**,resources/views/emails/** | .ai/rules/emails.md |
| app/Models/{Invoice,InvoiceItem}.php,app/Actions/Invoices/**,resources/views/pages/invoices/**,config/billing.php | .ai/rules/invoices.md |
| app/Models/{Patient,Visit,Note,Doctor,InsuranceCompany,HomeHealthAgency}.php,resources/views/pages/patients/**,app/Actions/Notes/**,app/Mail/NoteReadyForOffice.php | .ai/rules/mail.md |
| app/Http/Middleware/EnsureMfaIsEnabled.php,bootstrap/app.php,routes/admin.php,app/Providers/AppServiceProvider.php | .ai/rules/middleware-providers.md |
| app/Models/Note.php,app/Models/NoteAddendum.php,resources/views/pages/patients/notes/**,database/migrations/*note_addenda* | .ai/rules/migrations.md |
| resources/views/pages/⚡calendar.blade.php,app/Models/Visit.php, resources/views/pages/⚡calendar.blade.php,app/Models/User.php | .ai/rules/models.md |
| app/Models/Note.php,resources/views/pages/patients/notes/**,app/Actions/Notes/**,resources/views/pdf/notes/** | .ai/rules/notes.md |
| resources/views/pages/patients/⚡show.blade.php | .ai/rules/pages-patients.md |
| app/Models/PatientColor.php,resources/views/pages/⚡calendar.blade.php | .ai/rules/pages.md |
| app/Models/PatientAttachment.php,app/Models/Patient.php,resources/views/pages/patients/⚡show.blade.php,routes/patients.php,database/migrations/*patient_attachments* | .ai/rules/patients-migrations.md |
| app/Models/Patient.php,resources/views/pages/patients/** | .ai/rules/patients.md |
| app/Actions/Patients/**,resources/views/pdf/patients/**,routes/patients.php | .ai/rules/pdf-patients.md |
| resources/views/pages/admin/**,routes/admin.php,app/Providers/AppServiceProvider.php,bootstrap/app.php | .ai/rules/providers.md |
| resources/views/pages/admin/layout.blade.php,resources/views/pages/settings/layout.blade.php | .ai/rules/settings.md |
| app/Models/StagingForm.php,resources/views/pages/staging-forms/**,resources/views/pdf/staging-forms/**,routes/staging-forms.php | .ai/rules/staging-forms.md |
| resources/views/pages/⚡calendar.blade.php | .ai/rules/views-pages.md |
