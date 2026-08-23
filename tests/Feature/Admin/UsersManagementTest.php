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

test('admins can create a user with roles', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('createUser')
        ->set('name', 'Jane Staff')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('newUserRoles', ['staff'])
        ->call('saveUser')
        ->assertHasNoErrors();

    $created = User::where('email', 'jane@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Jane Staff')
        ->and($created->hasRole('staff'))->toBeTrue()
        ->and($created->email_verified_at)->not->toBeNull();
});

test('new user requires a unique email and confirmed password', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $existing = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('createUser')
        ->set('name', 'Jane Staff')
        ->set('email', $existing->email)
        ->set('password', 'password')
        ->set('password_confirmation', 'different')
        ->call('saveUser')
        ->assertHasErrors(['email', 'password']);
});

test('non-admins cannot create users even if they call the component directly', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::admin.users')
        ->call('createUser')
        ->assertForbidden();
});
