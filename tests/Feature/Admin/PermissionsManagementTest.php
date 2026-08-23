<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-admins cannot access the admin permissions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.permissions'))
        ->assertForbidden();
});

test('admins can create a permission', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.permissions')
        ->set('name', 'reports.view')
        ->call('createPermission')
        ->assertHasNoErrors();

    expect(Permission::where('name', 'reports.view')->exists())->toBeTrue();
});

test('permission names must be unique', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.permissions')
        ->set('name', 'users.view')
        ->call('createPermission')
        ->assertHasErrors(['name']);
});

test('admins can delete a permission', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $permission = Permission::create(['name' => 'temp.permission', 'guard_name' => 'web']);

    Livewire::actingAs($admin)
        ->test('pages::admin.permissions')
        ->call('deletePermission', $permission->id);

    expect(Permission::where('name', 'temp.permission')->exists())->toBeFalse();
});
