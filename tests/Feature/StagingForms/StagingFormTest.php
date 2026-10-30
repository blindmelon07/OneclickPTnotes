<?php

use App\Models\StagingForm;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('users without notes permission are forbidden from staging forms', function () {
    $user = User::factory()->create();
    $stagingForm = StagingForm::factory()->create();

    $this->actingAs($user)->get(route('staging-forms.index'))->assertForbidden();
    $this->actingAs($user)->get(route('staging-forms.create'))->assertForbidden();
    $this->actingAs($user)->get(route('staging-forms.show', $stagingForm))->assertForbidden();
    $this->actingAs($user)->get(route('staging-forms.edit', $stagingForm))->assertForbidden();
    $this->actingAs($user)->get(route('staging-forms.pdf', [$stagingForm, StagingForm::DOCUMENT_ALL]))->assertForbidden();
});

test('a pt assistant can see the staging forms pages', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $stagingForm = StagingForm::factory()->create();

    $this->actingAs($ptAssistant)->get(route('staging-forms.index'))->assertOk()->assertSee($stagingForm->patient_name);
    $this->actingAs($ptAssistant)->get(route('staging-forms.create'))->assertOk();
    $this->actingAs($ptAssistant)->get(route('staging-forms.show', $stagingForm))->assertOk();
    $this->actingAs($ptAssistant)->get(route('staging-forms.edit', $stagingForm))->assertOk();
});

test('the va fills in the evaluation and a staging form is created', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);

    Livewire::actingAs($ptAssistant)
        ->test('pages::staging-forms.edit')
        ->set('data.agency_name', 'Sunrise Home Health')
        ->set('data.patient_name', 'Jane Doe')
        ->set('data.functional_level', 'MIN assist')
        ->set('data.functional_overrides.feeding', 'Indep')
        ->set('data.homebound_reasons', ['Cannot ambulate >', 'Pain limits mobility'])
        ->set('data.precautions', ['Pacemaker', 'Cancer'])
        ->set('data.precautions_other', 'Fall risk')
        ->set('data.pta_schedule.0.visits', '2')
        ->set('data.pta_schedule.1.visits', '3')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('staging-forms.show', StagingForm::sole()));

    $stagingForm = StagingForm::sole();

    expect($stagingForm->patient_name)->toBe('Jane Doe')
        ->and($stagingForm->agency_name)->toBe('Sunrise Home Health')
        ->and($stagingForm->created_by)->toBe($ptAssistant->id)
        ->and($stagingForm->functionalLevelFor('dressing'))->toBe('MIN assist')
        ->and($stagingForm->functionalLevelFor('feeding'))->toBe('Indep')
        ->and($stagingForm->precautionSummary())->toBe('Cancer, Pacemaker, Fall risk')
        ->and($stagingForm->totalPtaVisits())->toBe(5);
});

test('free text precautions from older forms carry over as others', function () {
    $stagingForm = StagingForm::factory()->create([
        'data' => [...StagingForm::blankData(), 'precautions' => 'Fall risk'],
    ]);

    expect($stagingForm->formData()['precautions'])->toBe([])
        ->and($stagingForm->formData()['precautions_other'])->toBe('Fall risk')
        ->and($stagingForm->precautionSummary())->toBe('Fall risk');
});

test('patient and agency names are required and choices must come from the form', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);

    Livewire::actingAs($ptAssistant)
        ->test('pages::staging-forms.edit')
        ->set('data.functional_level', 'Somewhat okay')
        ->set('data.homebound_reasons', ['Made up reason'])
        ->set('data.precautions', ['Made up'])
        ->set('data.pta_schedule.0.visits', 'two')
        ->call('save')
        ->assertHasErrors([
            'data.agency_name' => 'required',
            'data.patient_name' => 'required',
            'data.functional_level',
            'data.homebound_reasons.0',
            'data.precautions.0',
            'data.pta_schedule.0.visits',
        ]);

    expect(StagingForm::count())->toBe(0);
});

test('editing a staging form updates it in place', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $stagingForm = StagingForm::factory()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::staging-forms.edit', ['stagingForm' => $stagingForm])
        ->assertSet('data.patient_name', $stagingForm->patient_name)
        ->set('data.patient_name', 'Renamed Patient')
        ->call('save')
        ->assertHasNoErrors();

    expect(StagingForm::count())->toBe(1)
        ->and($stagingForm->fresh()->patient_name)->toBe('Renamed Patient');
});

test('companion values copy from the evaluation unless set separately', function () {
    $stagingForm = StagingForm::factory()->create([
        'data' => array_replace_recursive(StagingForm::blankData(), [
            'gait' => ['level_surfaces' => '50 ft w/ FWW'],
            'balance' => ['sitting_static' => 'Fair', 'standing_static' => 'Poor+', 'standing_dynamic' => 'Poor'],
        ]),
    ]);

    expect($stagingForm->gaitValue('minutes_distance'))->toBe('50 ft w/ FWW')
        ->and($stagingForm->cannotAmbulateDistance())->toBe('50 ft w/ FWW')
        ->and($stagingForm->balanceGrade('sitting', 'dynamic'))->toBe('Fair')
        ->and($stagingForm->balanceGrade('standing', 'dynamic'))->toBe('Poor');
});

test('each document and the full packet render as pdfs', function (string $document) {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $stagingForm = StagingForm::factory()->create();

    $response = $this->actingAs($ptAssistant)->get(route('staging-forms.pdf', [$stagingForm, $document]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
})->with([...array_keys(StagingForm::documents()), StagingForm::DOCUMENT_ALL]);

test('fixed items are always checked on the companion forms', function () {
    $stagingForm = StagingForm::factory()->create();

    $html = view('pdf.staging-forms.document', [
        'form' => $stagingForm,
        'data' => $stagingForm->formData(),
        'documents' => [StagingForm::DOCUMENT_VISIT_NOTE, StagingForm::DOCUMENT_TEAM_CONFERENCE],
    ])->render();

    foreach ([...StagingForm::fixedVisitServices(), ...StagingForm::fixedTeamConferenceProblems()] as $item) {
        expect($html)->toMatch('/class="box on"><\/span>\s*'.preg_quote(e($item), '/').'/');
    }
});

test('an unknown document is not found', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $stagingForm = StagingForm::factory()->create();

    $this->actingAs($ptAssistant)->get("/staging-forms/{$stagingForm->id}/pdf/invoice")->assertNotFound();
});

test('a staging form can be deleted', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $stagingForm = StagingForm::factory()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::staging-forms.show', ['stagingForm' => $stagingForm])
        ->call('delete')
        ->assertRedirect(route('staging-forms.index'));

    expect(StagingForm::count())->toBe(0);
});
