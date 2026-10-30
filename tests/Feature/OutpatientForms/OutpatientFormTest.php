<?php

use App\Models\OutpatientForm;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('users without notes permission are forbidden from outpatient forms', function () {
    $user = User::factory()->create();
    $outpatientForm = OutpatientForm::factory()->create();

    $this->actingAs($user)->get(route('outpatient-forms.index'))->assertForbidden();
    $this->actingAs($user)->get(route('outpatient-forms.create'))->assertForbidden();
    $this->actingAs($user)->get(route('outpatient-forms.show', $outpatientForm))->assertForbidden();
    $this->actingAs($user)->get(route('outpatient-forms.edit', $outpatientForm))->assertForbidden();
    $this->actingAs($user)->get(route('outpatient-forms.pdf', [$outpatientForm, OutpatientForm::DOCUMENT_ALL]))->assertForbidden();
});

test('a pt assistant can see the outpatient forms pages', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $outpatientForm = OutpatientForm::factory()->create();

    $this->actingAs($ptAssistant)->get(route('outpatient-forms.index'))->assertOk()->assertSee($outpatientForm->patient_name);
    $this->actingAs($ptAssistant)->get(route('outpatient-forms.create'))->assertOk();
    $this->actingAs($ptAssistant)->get(route('outpatient-forms.show', $outpatientForm))->assertOk();
    $this->actingAs($ptAssistant)->get(route('outpatient-forms.edit', $outpatientForm))->assertOk();
});

test('the va fills in the forms and an outpatient form is created', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);

    Livewire::actingAs($ptAssistant)
        ->test('pages::outpatient-forms.edit')
        ->set('data.patient_name', 'Jane Doe')
        ->set('data.diagnosis', 'Right shoulder strain')
        ->set('data.hand', 'R-hand')
        ->set('data.ie.rom', 'Shoulder flexion 120')
        ->set('data.ie.precautions', ['Cancer', 'Pacemaker', 'Blood clot'])
        ->set('data.ie.precaution_details.Cancer', 'breast')
        ->set('tab', 're')
        ->set('data.re.progress.rom.value', 'Shoulder flexion 150')
        ->set('data.re.progress.rom.status', 'Improving')
        ->set('data.re.recommendation', OutpatientForm::RECOMMENDATION_CONTINUE)
        ->set('data.re.visits_left', '4')
        ->set('tab', 'dc')
        ->set('data.dc.discharge_reasons', ['Maximum Rehab Potential had been met'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('outpatient-forms.show', OutpatientForm::sole()));

    $outpatientForm = OutpatientForm::sole();

    expect($outpatientForm->patient_name)->toBe('Jane Doe')
        ->and($outpatientForm->diagnosis)->toBe('Right shoulder strain')
        ->and($outpatientForm->created_by)->toBe($ptAssistant->id)
        ->and($outpatientForm->baselineFor('re', 'rom'))->toBe('Shoulder flexion 120')
        ->and($outpatientForm->reEvaluationPrecautions())->toBe('Cancer breast, Pacemaker, Blood clot')
        ->and($outpatientForm->formData()['dc']['discharge_reasons'])->toBe(['Maximum Rehab Potential had been met']);
});

test('patient name is required and choices must come from the forms', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);

    Livewire::actingAs($ptAssistant)
        ->test('pages::outpatient-forms.edit')
        ->set('data.ie.rehab_potential', 'Excellent')
        ->set('data.ie.precautions', ['Made up'])
        ->set('data.ie.grip.right', 'strong')
        ->set('data.re.progress.rom.status', 'Declined')
        ->set('data.dc.progress.rom.status', 'Worse')
        ->set('data.dc.recommendation', 'something')
        ->call('save')
        ->assertHasErrors([
            'data.patient_name' => 'required',
            'data.ie.rehab_potential',
            'data.ie.precautions.0',
            'data.ie.grip.right',
            'data.re.progress.rom.status',
            'data.dc.progress.rom.status',
            'data.dc.recommendation',
        ]);

    expect(OutpatientForm::count())->toBe(0);
});

test('editing an outpatient form updates it in place', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $outpatientForm = OutpatientForm::factory()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::outpatient-forms.edit', ['outpatientForm' => $outpatientForm])
        ->assertSet('data.patient_name', $outpatientForm->patient_name)
        ->set('data.patient_name', 'Renamed Patient')
        ->call('save')
        ->assertHasNoErrors();

    expect(OutpatientForm::count())->toBe(1)
        ->and($outpatientForm->fresh()->patient_name)->toBe('Renamed Patient');
});

test('the re and dc baselines copy from the ie unless set separately', function () {
    $outpatientForm = OutpatientForm::factory()->create([
        'data' => array_replace_recursive(OutpatientForm::blankData(), [
            'ie' => [
                'rom' => 'Knee flexion 90',
                'mmt' => 'Quads 3/5',
                'walk_distance' => '100',
                'walk_with_device' => 'with',
                'assistive_device' => 'cane',
                'stair_flights' => '1',
                'palpation_sign' => '+',
                'palpation_grade' => '2',
                'tenderness_on' => 'medial knee',
                'adl_difficulties' => ['Squatting', 'Bending'],
            ],
            'dc' => ['progress' => ['rom' => ['baseline' => 'Knee flexion 95']]],
        ]),
    ]);

    expect($outpatientForm->baselineFor('re', 'rom'))->toBe('Knee flexion 90')
        ->and($outpatientForm->baselineFor('dc', 'rom'))->toBe('Knee flexion 95')
        ->and($outpatientForm->baselineFor('dc', 'mmt'))->toBe('Quads 3/5')
        ->and($outpatientForm->baselineFor('re', 'gait'))->toBe('Walks 100 feet/mins, with assistive device (cane)')
        ->and($outpatientForm->baselineFor('re', 'step_negotiation'))->toBe('Manages 1 flight(s) of stairs/steps')
        ->and($outpatientForm->baselineFor('re', 'tenderness'))->toBe('+ Grade 2 TTP on medial knee')
        ->and($outpatientForm->baselineFor('re', 'adl_function'))->toBe('Difficulty with Squatting, Bending')
        ->and($outpatientForm->baselineFor('re', 'spasm'))->toBe('');
});

test('the treatment plan carries from the ie to the re to the dc line by line', function () {
    $outpatientForm = OutpatientForm::factory()->create([
        'data' => array_replace_recursive(OutpatientForm::blankData(), [
            'ie' => ['plan' => ['modalities' => 'Hot pack', 'manual_therapy' => 'STM']],
            're' => ['plan' => ['modalities' => 'Ice pack']],
            'dc' => ['plan' => ['manual_therapy' => 'Joint mobs']],
        ]),
    ]);

    expect($outpatientForm->planFor('re', 'modalities'))->toBe('Ice pack')
        ->and($outpatientForm->planFor('re', 'manual_therapy'))->toBe('STM')
        ->and($outpatientForm->planFor('dc', 'modalities'))->toBe('Ice pack')
        ->and($outpatientForm->planFor('dc', 'manual_therapy'))->toBe('Joint mobs');
});

test('each document and the full set render as pdfs', function (string $document) {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $outpatientForm = OutpatientForm::factory()->create();

    $response = $this->actingAs($ptAssistant)->get(route('outpatient-forms.pdf', [$outpatientForm, $document]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
})->with([...array_keys(OutpatientForm::documents()), OutpatientForm::DOCUMENT_ALL]);

test('the re-evaluation prints the ie baseline beside the current value', function () {
    $outpatientForm = OutpatientForm::factory()->create([
        'data' => array_replace_recursive(OutpatientForm::blankData(), [
            'patient_name' => 'Jane Doe',
            'ie' => ['rom' => 'Wrist ext 30'],
            're' => ['progress' => ['rom' => ['value' => 'Wrist ext 55', 'status' => 'Improving']]],
        ]),
    ]);

    $html = view('pdf.outpatient-forms.document', [
        'form' => $outpatientForm,
        'data' => $outpatientForm->formData(),
        'documents' => [OutpatientForm::DOCUMENT_RE_EVALUATION],
    ])->render();

    expect($html)->toContain('Wrist ext 30')
        ->toContain('Wrist ext 55')
        ->toMatch('/class="box on"><\/span>Improving/');
});

test('an unknown document is not found', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $outpatientForm = OutpatientForm::factory()->create();

    $this->actingAs($ptAssistant)->get("/outpatient-forms/{$outpatientForm->id}/pdf/invoice")->assertNotFound();
});

test('an outpatient form can be deleted', function () {
    $ptAssistant = User::factory()->create()->assignRole(User::ROLE_PT_ASSISTANT);
    $outpatientForm = OutpatientForm::factory()->create();

    Livewire::actingAs($ptAssistant)
        ->test('pages::outpatient-forms.show', ['outpatientForm' => $outpatientForm])
        ->call('delete')
        ->assertRedirect(route('outpatient-forms.index'));

    expect(OutpatientForm::count())->toBe(0);
});
