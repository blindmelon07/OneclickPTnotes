---
paths:
  - 'config/backup.php,config/filesystems.php,config/database.php,routes/console.php'
---

# Config

## Automated backups via spatie/laravel-backup — 'backups' disk, storage/app/private only
config/backup.php was hand-copied from the package stub (not vendor:publish'd — that command prompts interactively and hangs non-interactively here). Deliberately deviates from the package default in two ways: (1) file source is `storage_path('app/private')` only, NOT `base_path()` — backing up the whole app would sweep `.env` (secrets) into the archive; code is already in git. (2) destination disk is a new `backups` disk (config/filesystems.php, root storage_path('app/backups')), kept separate from `local` (root storage_app/private) so backup zips never get included in the next backup's own file source. Encryption is `password => env('BACKUP_ARCHIVE_PASSWORD')` — must be set in production or archives are unencrypted. Scheduled in routes/console.php: `backup:clean` 01:00, `backup:run` 01:30, `backup:monitor` 02:00 daily. On Windows/XAMPP, `mysqldump` often isn't on PATH — config/database.php's `mysql.dump.dump_binary_path` reads `env('MYSQLDUMP_PATH')`, which must be the *directory* containing mysqldump.exe (trailing slash), not the full exe path — db-dumper appends `mysqldump` itself. Tests can't run a real `backup:run` against the in-memory sqlite test DB (no db file to dump), so tests/Feature/BackupConfigurationTest.php only asserts config wiring + that `backup:list` runs; the actual dump was manually verified via `php artisan backup:run` locally against MySQL. See [[note-addendum-instead-of-edit]] and [[hipaa-mfa-admin-gate]] for sibling HIPAA-compliance work.
