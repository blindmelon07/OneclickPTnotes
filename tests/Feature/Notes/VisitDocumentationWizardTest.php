<?php

use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function wizardFor(User $user, Patient $patient, Visit $visit)
{
    return Livewire::actingAs($user)->test('pages::patients.visits.document', [
        'patient' => $patient,
        'visit' => $visit,
    ]);
}

const SIGNATURE_DATA_URI = 'data:image/png;base64,'.'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

function scheduledVisit(?User $therapist = null): array
{
    $ptAssistant = $therapist ?? User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create(['pt_assistant_id' => $ptAssistant->id]);
    $visit = Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $ptAssistant->id,
        'scheduled_at' => now()->addDay(),
    ]);

    return [$ptAssistant, $patient, $visit];
}

test('the wizard starts with only the two required forms', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->assertOk()
        ->assertSet('step', 1)
        ->assertCount('activeSteps', 2);
});

test('opting into both optional forms makes it a four step wizard', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('include.missed_visit', true)
        ->set('include.communication', true)
        ->assertCount('activeSteps', 4);
});

test('the wizard prefills the agency and patient from the visit', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->assertSet('routeSheet.patient_name', $patient->name)
        ->assertSet('routeSheet.agency_name', $patient->homeHealthAgency->name)
        ->assertSet('routeSheet.visit_date', $visit->scheduled_at->toDateString())
        ->assertSet('visitNote.date', $visit->scheduled_at->toDateString());
});

test('the route sheet will not advance without both drawn signatures', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->call('nextStep')
        ->assertHasErrors(['routeSheetSignature', 'routeSheetStaffSignature'])
        ->assertSet('step', 1);
});

test('the pt assistant signature alone is not enough to advance', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->call('nextStep')
        ->assertHasErrors('routeSheetSignature')
        ->assertHasNoErrors('routeSheetStaffSignature')
        ->assertSet('step', 1);
});

test('both drawn signatures are stored as files on the route sheet note', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->call('submit')
        ->assertHasNoErrors();

    $routeSheet = Note::where('type', Note::TYPE_ROUTE_SHEET)->sole();

    expect($routeSheet->isPatientSigned())->toBeTrue();
    expect($routeSheet->isSigned())->toBeTrue();
    expect($routeSheet->data)->not->toHaveKey('rows');
    Storage::disk('local')->assertExists($routeSheet->patient_signature_path);
    Storage::disk('local')->assertExists($routeSheet->signature_path);
});

test('the route sheet pdf dates the assistant signature to the visit date', function () {
    [$user, $patient, $visit] = scheduledVisit();
    $visitDate = now()->addWeek()->toDateString();

    wizardFor($user, $patient, $visit)
        ->set('routeSheet.visit_date', $visitDate)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->call('submit')
        ->assertHasNoErrors();

    $routeSheet = Note::where('type', Note::TYPE_ROUTE_SHEET)->sole();

    expect($routeSheet->data['visit_date'])->toBe($visitDate);
    expect($routeSheet->created_at->toDateString())->not->toBe($visitDate);
});

test('the supervising therapist is countersigned automatically once the assistant signs', function () {
    [$user, $patient, $visit] = scheduledVisit();

    $signature = public_path('signatures/test-supervising-therapist.png');
    @mkdir(dirname($signature), 0777, true);
    file_put_contents($signature, base64_decode(explode(',', SIGNATURE_DATA_URI, 2)[1]));
    config(['notes.letterhead.supervising_therapist_signature' => 'signatures/test-supervising-therapist.png']);

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Note::where('type', Note::TYPE_ROUTE_SHEET)->sole()->isCountersigned())->toBeTrue();

    unlink($signature);
});

test('nothing is countersigned when no signature is on file', function () {
    [$user, $patient, $visit] = scheduledVisit();

    config(['notes.letterhead.supervising_therapist_signature' => null]);

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Note::where('type', Note::TYPE_ROUTE_SHEET)->sole()->isCountersigned())->toBeFalse();
});

test('the mr number written on the route sheet carries onto the other forms', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheet.mr_number', 'MR-4821')
        ->assertSet('visitNote.mr_number', 'MR-4821')
        ->assertSet('missedVisit.mr_number', 'MR-4821')
        ->assertSet('communication.mr_number', 'MR-4821');
});

test('the agency name written on the route sheet carries onto the other forms', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheet.agency_name', 'Bayside Home Health')
        ->assertSet('visitNote.agency_name', 'Bayside Home Health')
        ->assertSet('missedVisit.agency_name', 'Bayside Home Health')
        ->assertSet('communication.agency_name', 'Bayside Home Health');
});

test('the assistant signature drawn on the route sheet carries onto the other forms', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->assertSet('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->assertSet('missedVisitStaffSignature', SIGNATURE_DATA_URI)
        ->assertSet('communicationStaffSignature', SIGNATURE_DATA_URI);
});

test('a signature already drawn on a later form is not overwritten', function () {
    [$user, $patient, $visit] = scheduledVisit();
    $other = 'data:image/png;base64,OTHER';

    wizardFor($user, $patient, $visit)
        ->set('visitNoteStaffSignature', $other)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->assertSet('visitNoteStaffSignature', $other)
        ->assertSet('communicationStaffSignature', SIGNATURE_DATA_URI);
});

test('a carried signature can be cleared to sign again', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->call('clearSignature', 'visitNoteStaffSignature')
        ->assertSet('visitNoteStaffSignature', null)
        ->assertSet('routeSheetStaffSignature', SIGNATURE_DATA_URI);
});

test('clearSignature refuses a property that is not a signature', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->call('clearSignature', 'patient')
        ->assertForbidden();
});

test('a step will not advance while its required fields are empty', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheet.agency_name', '')
        ->call('nextStep')
        ->assertHasErrors('routeSheet.agency_name')
        ->assertSet('step', 1);
});

test('finishing the wizard saves one note with a pdf per completed form', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training, tolerated well.')
        ->call('nextStep')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Note::pluck('type')->all())->toBe([Note::TYPE_ROUTE_SHEET, Note::TYPE_VISIT_NOTE]);

    Note::each(function (Note $note) use ($visit) {
        expect($note->visit_id)->toBe($visit->id);
        expect($note->pdf_path)->not->toBeNull();
        Storage::disk('local')->assertExists($note->pdf_path);
    });
});

test('the visit note cannot be saved once its carried signature is cleared', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->set('visitNote.observations', 'Gait training.')
        ->call('clearSignature', 'visitNoteStaffSignature')
        ->call('submit')
        ->assertHasErrors('visitNoteStaffSignature');

    expect(Note::count())->toBe(0);
});

test('the visit note stores the assistant signature and countersignature', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->call('submit')
        ->assertHasNoErrors();

    $visitNote = Note::where('type', Note::TYPE_VISIT_NOTE)->sole();

    expect($visitNote->isSigned())->toBeTrue();
    expect($visitNote->isPatientSigned())->toBeFalse();
    Storage::disk('local')->assertExists($visitNote->signature_path);
});

test('the optional forms are saved only when opted into', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('include.missed_visit', true)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Visit attempted.')
        ->set('missedVisit.reasons', ['MD appointment'])
        ->set('missedVisit.rescheduled', 'no')
        ->set('missedVisit.not_rescheduled_reason', 'Patient declined.')
        ->set('missedVisitStaffSignature', SIGNATURE_DATA_URI)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Note::pluck('type')->all())->toBe([
        Note::TYPE_ROUTE_SHEET, Note::TYPE_VISIT_NOTE, Note::TYPE_MISSED_VISIT,
    ]);
    expect(Note::where('type', Note::TYPE_COMMUNICATION)->exists())->toBeFalse();
});

test('submitting with an incomplete later step jumps back to it', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('include.missed_visit', true)
        ->set('include.communication', true)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->set('missedVisit.reasons', ['MD appointment'])
        ->set('missedVisit.rescheduled', 'yes')
        ->set('communication.message', 'Patient reports new pain.')
        ->call('clearSignature', 'missedVisitStaffSignature')
        ->call('submit')
        ->assertHasErrors('missedVisitStaffSignature')
        ->assertSet('step', 3);

    expect(Note::count())->toBe(0);
});

test('every optional form stores its signature when completed', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('include.missed_visit', true)
        ->set('include.communication', true)
        ->set('routeSheetSignature', SIGNATURE_DATA_URI)
        ->set('routeSheetStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNoteStaffSignature', SIGNATURE_DATA_URI)
        ->set('visitNote.observations', 'Gait training.')
        ->set('missedVisit.reasons', ['MD appointment'])
        ->set('missedVisit.rescheduled', 'yes')
        ->set('missedVisitStaffSignature', SIGNATURE_DATA_URI)
        ->set('communication.message', 'Patient reports new pain.')
        ->set('communicationStaffSignature', SIGNATURE_DATA_URI)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Note::count())->toBe(4);

    Note::each(function (Note $note) {
        expect($note->isSigned())->toBeTrue();
        Storage::disk('local')->assertExists($note->signature_path);
        Storage::disk('local')->assertExists($note->pdf_path);
    });
});

test('turning an optional form back off drops it from the wizard', function () {
    [$user, $patient, $visit] = scheduledVisit();

    wizardFor($user, $patient, $visit)
        ->set('include.communication', true)
        ->set('step', 3)
        ->set('include.communication', false)
        ->assertCount('activeSteps', 2)
        ->assertSet('step', 2);
});

test('a pt assistant cannot document a visit for a patient who is not theirs', function () {
    [, , $visit] = scheduledVisit();
    $otherAssistant = User::factory()->create()->assignRole('PT Assistant');

    $this->actingAs($otherAssistant)
        ->get(route('patients.visits.document', [$visit->patient, $visit]))
        ->assertForbidden();
});

test('a visit belonging to another patient is not documentable under this one', function () {
    [$user, $patient] = scheduledVisit($therapist = User::factory()->create()->assignRole('PT Assistant'));
    [, , $otherVisit] = scheduledVisit();

    $this->actingAs($user)
        ->get(route('patients.visits.document', [$patient, $otherVisit]))
        ->assertNotFound();
});

test('the patient page links each upcoming visit to the wizard', function () {
    [$user, $patient, $visit] = scheduledVisit();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Proceed')
        ->assertSee(route('patients.visits.document', [$patient, $visit]), escape: false);
});

test('an already documented visit cannot be opened in the wizard again', function () {
    [$user, $patient, $visit] = scheduledVisit();

    Note::factory()->create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'type' => Note::TYPE_ROUTE_SHEET,
    ]);

    $this->actingAs($user)
        ->get(route('patients.visits.document', [$patient, $visit]))
        ->assertForbidden();
});

test('a documented visit shows notes done instead of the proceed button', function () {
    [$user, $patient, $visit] = scheduledVisit();

    Note::factory()->create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'type' => Note::TYPE_ROUTE_SHEET,
    ]);

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Notes done')
        ->assertDontSee('Proceed')
        ->assertDontSee(route('patients.visits.document', [$patient, $visit]), escape: false);
});
