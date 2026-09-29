<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

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
    $admin = User::factory()->withTwoFactor()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee($admin->name);
});

test('admins without two-factor authentication are redirected to security settings', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertRedirect(route('security.edit'));
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

test('admins can disable a user, which also kills their active sessions', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'fake-session-id',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => base64_encode('fake'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleDisabled', $target->id);

    expect($target->fresh()->isDisabled())->toBeTrue();
    expect(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse();

    $activity = Activity::where('log_name', 'user')
        ->where('subject_id', $target->id)
        ->where('description', 'user disabled')
        ->first();

    expect($activity)->not->toBeNull();
});

test('admins can re-enable a disabled user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create(['disabled_at' => now()]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleDisabled', $target->id);

    expect($target->fresh()->isDisabled())->toBeFalse();
});

test('an admin cannot disable their own account', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleDisabled', $admin->id)
        ->assertForbidden();

    expect($admin->fresh()->isDisabled())->toBeFalse();
});

test('non-admins cannot disable users even if they call the component directly', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::admin.users')
        ->call('toggleDisabled', $target->id)
        ->assertForbidden();
});

test('admins can sign a user out everywhere without disabling them', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'fake-session-id',
        'user_id' => $target->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => base64_encode('fake'),
        'last_activity' => now()->timestamp,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('signOutEverywhere', $target->id);

    expect($target->fresh()->isDisabled())->toBeFalse();
    expect(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse();
});
