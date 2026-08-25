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
        'invoices.manage',
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
     * Corporate Secretary: oversight of staff and role assignments, no edit rights.
     *
     * @var array<int, string>
     */
    protected array $corporateSecretaryPermissions = [
        'users.view',
        'roles.view',
    ];

    /**
     * Biller: same clinical access as Staff, needed to see patient/visit info for billing.
     *
     * @var array<int, string>
     */
    protected array $billerPermissions = [
        'patients.manage',
        'notes.manage',
        'invoices.manage',
    ];

    /**
     * Office Manager: day-to-day clinical access plus managing staff accounts.
     *
     * @var array<int, string>
     */
    protected array $officeManagerPermissions = [
        'patients.manage',
        'notes.manage',
        'users.view',
        'users.manage',
        'invoices.manage',
    ];

    /**
     * PT Assistant: same clinical access as Staff/Biller, needed to view and document patient visits.
     *
     * @var array<int, string>
     */
    protected array $ptAssistantPermissions = [
        'patients.manage',
        'notes.manage',
        'invoices.manage',
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

        $corporateSecretary = Role::firstOrCreate(['name' => 'Corporate Secretary']);
        $corporateSecretary->syncPermissions($this->corporateSecretaryPermissions);

        $biller = Role::firstOrCreate(['name' => 'Biller']);
        $biller->syncPermissions($this->billerPermissions);

        $officeManager = Role::firstOrCreate(['name' => 'Office Manager']);
        $officeManager->syncPermissions($this->officeManagerPermissions);

        $ptAssistant = Role::firstOrCreate(['name' => 'PT Assistant']);
        $ptAssistant->syncPermissions($this->ptAssistantPermissions);
    }
}
