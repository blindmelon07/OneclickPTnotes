<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authenticated staff can view the patients list', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee($patient->name);
});

test('guests cannot view patients', function () {
    $this->get(route('patients.index'))->assertRedirect(route('login'));
});

test('users without patients.manage cannot create a patient', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.index')
        ->set('name', 'Jane Doe')
        ->call('createPatient')
        ->assertForbidden();

    expect(Patient::count())->toBe(0);
});

test('admins can create a patient', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::patients.index')
        ->set('name', 'Jane Doe')
        ->set('status', Patient::STATUS_ACTIVE)
        ->call('createPatient');

    expect(Patient::where('name', 'Jane Doe')->exists())->toBeTrue();
});

test('the patient profile shows demographic and treatment info', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create(['diagnosis' => 'Post-op knee replacement']);

    $this->actingAs($admin)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->name)
        ->assertSee('Post-op knee replacement');
});

test('a patient can be assigned to a pt assistant', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('pt_assistant_id', $ptAssistant->id)
        ->call('updatePatient')
        ->assertHasNoErrors();

    expect($patient->fresh()->pt_assistant_id)->toBe($ptAssistant->id);
    expect($patient->fresh()->ptAssistant->name)->toBe($ptAssistant->name);
});
