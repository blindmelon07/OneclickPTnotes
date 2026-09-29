---
paths:
  - 'app/Models/User.php,app/Providers/AppServiceProvider.php,resources/views/pages/admin/⚡users.blade.php,database/migrations/*disabled_at*'
---

# Admin Migrations

## User disable/enable + force sign-out relies on the database session driver
`users.disabled_at` (nullable timestamp) gates login: `AppServiceProvider::configureDisabledAccountLogout()` listens for the `Illuminate\Auth\Events\Login` event (fires after credential/2FA/passkey checks succeed — the one point every auth path converges) and if `$event->user->isDisabled()`, immediately logs them back out, invalidates the session, and throws a `ValidationException` so it surfaces as a normal login-form error. Admin panel actions `toggleDisabled()` and `signOutEverywhere()` (resources/views/pages/admin/⚡users.blade.php) additionally do `DB::table('sessions')->where('user_id', $id)->delete()` to kill any *already-active* session immediately, not just block future logins — **this only works because `SESSION_DRIVER=database`** (config/session.php default, and the app's actual `.env`). If the session driver is ever changed to `array`/`redis`/`cookie`/etc., these two actions silently stop force-ending active sessions (login-blocking via the Login event still works regardless of driver). An admin cannot disable their own account (`toggleDisabled` aborts). See [[hipaa-mfa-admin-gate]] and [[note-addendum-instead-of-edit]] for the sibling HIPAA-driven work this was added alongside — this one closes the "user accounts can be disabled immediately" / "former employees removed promptly" checklist gap.
