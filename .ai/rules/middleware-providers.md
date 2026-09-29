---
paths:
  - 'app/Http/Middleware/EnsureMfaIsEnabled.php,bootstrap/app.php,routes/admin.php,app/Providers/AppServiceProvider.php'
---

# Middleware Providers

## Admins must have 2FA or a passkey enabled (HIPAA MFA gate)
`EnsureMfaIsEnabled` (alias `mfa.enabled`) redirects any user with the `admin` role to `security.edit` if they have neither `hasEnabledTwoFactorAuthentication()` nor `hasPasskeysEnabled()`. Applied on the `admin.` route group in routes/admin.php and registered in `Livewire::addPersistentMiddleware()` (AppServiceProvider) so it re-fires on Livewire AJAX actions within already-mounted admin pages, not just the initial page load — same pattern as `RoleMiddleware`. The check is role-scoped inside the middleware itself (not just via route middleware) since persistent middleware re-resolves against the original route's middleware list. Non-admin roles are intentionally NOT gated (scope decision: admins only, no grace period). Tests creating an HTTP-routed admin user for an `admin.*` route must use `User::factory()->withTwoFactor()->create()->assignRole('admin')`; `Livewire::actingAs()->test(...)` bypasses this (and all route middleware) since it never goes through HTTP.
