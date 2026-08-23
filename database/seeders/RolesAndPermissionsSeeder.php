<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The permissions managed by the admin panel.
     *
     * @var array<int, string>
     */
    protected array $permissions = [
        'users.view',
        'users.manage',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        'patients.manage',
        'notes.manage',
    ];

    /**
     * Permissions granted to every authenticated staff member by default,
     * beyond the base ability (shared by all roles) to view/create patients and notes.
     *
     * @var array<int, string>
     */
    protected array $staffPermissions = [
        'patients.manage',
        'notes.manage',
    ];

    /**
     * Seed the roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($this->permissions);

        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions($this->staffPermissions);

        Role::firstOrCreate(['name' => 'user']);
    }
}
