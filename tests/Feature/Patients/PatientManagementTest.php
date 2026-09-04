<?php

use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

test('a pt assistant only sees the patients assigned to their account', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $mine = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);
    $theirs = Patient::factory()->create();

    $this->actingAs($ptAssistant)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee($mine->name)
        ->assertDontSee($theirs->name);
});

test('a pt assistant cannot open the chart of an unassigned patient', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $theirs = Patient::factory()->create();

    $this->actingAs($ptAssistant)
        ->get(route('patients.show', $theirs))
        ->assertForbidden();

    $this->actingAs($ptAssistant)
        ->get(route('patients.notes.create', ['patient' => $theirs, 'type' => Note::TYPE_FU]))
        ->assertForbidden();
});

test('a pt assistant can open the chart of an assigned patient', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $mine = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);

    $this->actingAs($ptAssistant)
        ->get(route('patients.show', $mine))
        ->assertOk()
        ->assertSee($mine->name);
});

test('an admin unticking the caseload restriction restores the full roster', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $theirs = Patient::factory()->create();

    Role::findByName('PT Assistant')->revokePermissionTo(User::RESTRICT_OWN_CASELOAD);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($ptAssistant->fresh())
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee($theirs->name);
});

test('roles without the caseload restriction see every patient', function () {
    $biller = User::factory()->create()->assignRole('Biller');
    $patient = Patient::factory()->create();

    $this->actingAs($biller)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee($patient->name);
});

test('a patient created by a pt assistant is assigned to them', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');

    Livewire::actingAs($ptAssistant)
        ->test('pages::patients.index')
        ->set('name', 'Jane Doe')
        ->set('status', Patient::STATUS_ACTIVE)
        ->call('createPatient');

    expect(Patient::where('name', 'Jane Doe')->first()->pt_assistant_id)->toBe($ptAssistant->id);
});

test('a pt assistant cannot reassign their patient to someone else', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $other = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);

    Livewire::actingAs($ptAssistant)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('pt_assistant_id', $other->id)
        ->call('updatePatient')
        ->assertHasNoErrors();

    expect($patient->fresh()->pt_assistant_id)->toBe($ptAssistant->id);
});

test('a patient with empty optional columns opens without error', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create([
        'address' => null,
        'phone' => null,
        'diagnosis' => null,
        'cert_period' => null,
        'pt_freq' => null,
        'doctor_id' => null,
        'insurance_company_id' => null,
        'home_health_agency_id' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($patient->name);
});

test('a patient created through the roster form opens without error', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::patients.index')
        ->set('name', 'Bryan')
        ->set('phone', '09673880454')
        ->set('diagnosis', 'pilay ako')
        ->set('approved_visits', 5)
        ->set('status', Patient::STATUS_ACTIVE)
        ->call('createPatient')
        ->assertHasNoErrors();

    $this->actingAs($admin)
        ->get(route('patients.show', Patient::where('name', 'Bryan')->sole()))
        ->assertOk();
});

test('a patient can be assigned straight from the care team card', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create(['pt_assistant_id' => null]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('assignPtAssistant', $ptAssistant->id)
        ->assertHasNoErrors();

    expect($patient->fresh()->pt_assistant_id)->toBe($ptAssistant->id);
});

test('an assignment can be cleared again', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('assignPtAssistant')
        ->assertHasNoErrors();

    expect($patient->fresh()->pt_assistant_id)->toBeNull();
});

test('only a pt assistant account can be assigned', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $biller = User::factory()->create()->assignRole('Biller');
    $patient = Patient::factory()->create(['pt_assistant_id' => null]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('assignPtAssistant', $biller->id)
        ->assertStatus(422);

    expect($patient->fresh()->pt_assistant_id)->toBeNull();
});

test('a restricted pt assistant cannot assign from the card', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $other = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);

    Livewire::actingAs($ptAssistant)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('assignPtAssistant', $other->id)
        ->assertForbidden();

    expect($patient->fresh()->pt_assistant_id)->toBe($ptAssistant->id);
});

test('untouched text fields are stored as null rather than empty strings', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create(['address' => null, 'cert_period' => null, 'pt_freq' => null]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('name', 'byron')
        ->call('updatePatient')
        ->assertHasNoErrors();

    $patient->refresh();

    expect($patient->name)->toBe('byron');
    expect($patient->address)->toBeNull();
    expect($patient->cert_period)->toBeNull();
    expect($patient->pt_freq)->toBeNull();
});

test('the roster loads on a database with no roles seeded', function () {
    Role::query()->delete();

    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee($patient->name);
});

test('a patient can be moved to one of the extended roster statuses', function (string $status, string $label) {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create(['status' => Patient::STATUS_ACTIVE]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('status', $status)
        ->call('updatePatient')
        ->assertHasNoErrors();

    expect($patient->fresh()->status)->toBe($status);
    expect($patient->fresh()->statusLabel())->toBe($label);

    $this->actingAs($admin)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee($label);
})->with([
    [Patient::STATUS_ON_HOLD, 'On-Hold'],
    [Patient::STATUS_PATIENT_REFUSED, 'Patient Refused HHPT'],
    [Patient::STATUS_PASSED_AWAY, 'Px Passed Away'],
    [Patient::STATUS_STOP_ORDERED, 'HHA/MD ordered to stop HHPT'],
    [Patient::STATUS_HAVING_SURGERY, 'Having surgery'],
]);

test('the roster status filter narrows the list to the chosen status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $onHold = Patient::factory()->create(['status' => Patient::STATUS_ON_HOLD]);
    $active = Patient::factory()->create(['status' => Patient::STATUS_ACTIVE]);

    Livewire::actingAs($admin)
        ->test('pages::patients.index')
        ->set('statusFilter', Patient::STATUS_ON_HOLD)
        ->assertSee($onHold->name)
        ->assertDontSee($active->name);
});

test('the patient page loads on a database with no roles seeded', function () {
    $patient = Patient::factory()->create();

    Role::query()->delete();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertOk();

    expect(User::supervisingAdmin())->toBeNull();
});
