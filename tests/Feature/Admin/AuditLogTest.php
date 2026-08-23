<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-admins cannot access the audit log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.audit-log'))
        ->assertForbidden();
});

test('admins can view the audit log', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.audit-log'))
        ->assertOk()
        ->assertSee('doctor');
});

test('creating a patient is recorded in the audit log', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::patients.index')
        ->set('name', 'Audit Patient')
        ->call('createPatient');

    $patient = Patient::where('name', 'Audit Patient')->sole();

    $activity = Activity::where('log_name', 'patient')
        ->where('subject_type', Patient::class)
        ->where('subject_id', $patient->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($admin->id);
    expect($activity->description)->toBe('created');
});

test('assigning a role to a user is recorded in the audit log', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $target = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('editRoles', $target->id)
        ->set('selectedRoles', ['user'])
        ->call('saveRoles');

    $activity = Activity::where('log_name', 'user')
        ->where('description', 'roles updated')
        ->where('subject_id', $target->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties['roles'])->toBe(['user']);
});
