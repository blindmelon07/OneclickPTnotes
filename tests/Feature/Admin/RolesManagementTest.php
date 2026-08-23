<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-admins cannot access the admin roles page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.roles'))
        ->assertForbidden();
});

test('admins can create a role with permissions', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.roles')
        ->call('createRole')
        ->set('name', 'editor')
        ->set('selectedPermissions', ['users.view'])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::where('name', 'editor')->first();

    expect($role)->not->toBeNull();
    expect($role->hasPermissionTo('users.view'))->toBeTrue();
});

test('role names must be unique', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.roles')
        ->call('createRole')
        ->set('name', 'admin')
        ->call('saveRole')
        ->assertHasErrors(['name']);
});

test('the built-in admin role cannot be deleted', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $adminRole = Role::where('name', 'admin')->firstOrFail();

    Livewire::actingAs($admin)
        ->test('pages::admin.roles')
        ->call('deleteRole', $adminRole->id);

    expect(Role::where('name', 'admin')->exists())->toBeTrue();
});

test('admins can delete a custom role', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $role = Role::create(['name' => 'temp', 'guard_name' => 'web']);

    Livewire::actingAs($admin)
        ->test('pages::admin.roles')
        ->call('deleteRole', $role->id);

    expect(Role::where('name', 'temp')->exists())->toBeFalse();
});
