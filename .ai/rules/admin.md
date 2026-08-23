---
paths:
  - 'app/Models/*.php,resources/views/pages/admin/⚡audit-log.blade.php,resources/views/pages/admin/**'
---

# Admin

## Audit logging via spatie/laravel-activitylog v5
Audit trail uses spatie/laravel-activitylog ^5.1 — note its v5 namespaces differ from older docs/examples floating around: trait is `Spatie\Activitylog\Models\Concerns\LogsActivity` (not `...\Traits\LogsActivity`), options class is `Spatie\Activitylog\Support\LogOptions` (not `Spatie\Activitylog\LogOptions`), and the "skip empty diffs" method is `dontLogEmptyChanges()` (not `dontSubmitEmptyLogs()`).

Every core model (Patient, Note, Visit, Doctor, InsuranceCompany, HomeHealthAgency, User) has `LogsActivity` + `getActivitylogOptions()` with an explicit `logOnly([...])` field allowlist — never log secrets (User only logs name/email, never password/2FA fields). Causer is auto-set from the authenticated user; no manual wiring needed.

Pivot-table changes (role/permission assignment via Spatie Permission's `syncRoles`/`syncPermissions`) do **not** fire model `updated` events, so they're NOT auto-captured — those are logged manually with the `activity()` helper inside the admin Livewire actions (see `resources/views/pages/admin/⚡users.blade.php` `saveRoles()`, `⚡roles.blade.php` `saveRole()`/`deleteRole()`, `⚡permissions.blade.php`). Follow that same manual pattern for any new non-Eloquent-event action that needs auditing.

`DatabaseSeeder` uses `WithoutModelEvents`, so `PatientDataSeeder`'s bulk sample data intentionally does NOT populate the audit log — only real app actions do. Viewer: `/admin/audit-log` (admin-only, via `role:admin` route middleware already on `routes/admin.php`). See [[patient-note-clinical-documentation-workflow]] and [[rbac-via-spatie-laravel-permission]].
