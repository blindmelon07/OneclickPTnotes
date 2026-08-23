<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-admins cannot access the admin users page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

test('admins can view the users list', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee($admin->name);
});

test('admins can assign a role to a user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('editRoles', $target->id)
        ->set('selectedRoles', ['user'])
        ->call('saveRoles');

    expect($target->fresh()->hasRole('user'))->toBeTrue();
});

test('non-admins cannot assign roles even if they call the component directly', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::admin.users')
        ->call('editRoles', $target->id)
        ->assertForbidden();
});
