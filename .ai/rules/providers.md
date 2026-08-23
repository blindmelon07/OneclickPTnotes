---
paths:
  - 'resources/views/pages/admin/**,routes/admin.php,app/Providers/AppServiceProvider.php,bootstrap/app.php'
---

# Providers

## RBAC via spatie/laravel-permission
Roles/permissions are managed with spatie/laravel-permission (not a custom implementation). `User` uses `HasRoles`. Permission naming convention is `resource.action` (e.g. `users.manage`, `roles.view`). Seed source of truth is `database/seeders/RolesAndPermissionsSeeder.php`, called from `DatabaseSeeder`. Built-in roles `admin` and `user` are protected from deletion in the admin UI.

`role`/`permission`/`role_or_permission` middleware aliases are registered in `bootstrap/app.php`. `Spatie\Permission\Middleware\RoleMiddleware` is registered as Livewire persistent middleware in `AppServiceProvider::boot()` so re-auth happens on Livewire AJAX requests too, not just initial page load — do the same for any new Spatie middleware added later.

Admin panel lives at `/admin` (routes in `routes/admin.php`, required from `routes/web.php`), gated by `role:admin` at the route level and mirrors the `resources/views/pages/settings` Livewire 4 SFC + navlist-layout pattern (see `resources/views/pages/admin/layout.blade.php`). Component methods additionally guard with `abort_unless(auth()->user()->can('x.manage'), 403)` since Livewire component actions aren't covered by route middleware once mounted directly in tests.
