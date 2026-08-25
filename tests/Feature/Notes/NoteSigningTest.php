<?php

use App\Mail\NoteReadyForOffice;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

const FAKE_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

beforeEach(function () {
    Storage::fake('local');
    Mail::fake();
});

test('staff can fill out, sign, and email a note to the office', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.create', ['patient' => $patient, 'type' => Note::TYPE_IE])
        ->set('fields.assessment', 'Patient tolerated treatment well.')
        ->set('fields.plan', 'Continue POC 2x/week.')
        ->set('signature', FAKE_SIGNATURE)
        ->set('patientSignature', FAKE_SIGNATURE)
        ->call('signAndSend')
        ->assertHasNoErrors();

    $note = Note::sole();

    expect($note->patient_id)->toBe($patient->id);
    expect($note->author_id)->toBe($user->id);
    expect($note->isSigned())->toBeTrue();
    expect($note->isPatientSigned())->toBeTrue();
    expect($note->isEmailed())->toBeTrue();
    expect($note->signature_path)->not->toBeNull();
    expect($note->patient_signature_path)->not->toBeNull();
    expect($note->pdf_path)->not->toBeNull();

    Storage::disk('local')->assertExists($note->signature_path);
    Storage::disk('local')->assertExists($note->patient_signature_path);
    Storage::disk('local')->assertExists($note->pdf_path);

    Mail::assertSent(NoteReadyForOffice::class, function (NoteReadyForOffice $mail) use ($note) {
        return $mail->note->is($note)
            && $mail->hasTo(config('notes.office_email'));
    });
});

test('a note cannot be sent without a staff signature', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.create', ['patient' => $patient, 'type' => Note::TYPE_IE])
        ->set('fields.assessment', 'Assessment text.')
        ->set('fields.plan', 'Plan text.')
        ->set('patientSignature', FAKE_SIGNATURE)
        ->call('signAndSend')
        ->assertHasErrors('signature');

    expect(Note::count())->toBe(0);
    Mail::assertNothingSent();
});

test('a note cannot be sent without a patient signature', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.create', ['patient' => $patient, 'type' => Note::TYPE_IE])
        ->set('fields.assessment', 'Assessment text.')
        ->set('fields.plan', 'Plan text.')
        ->set('signature', FAKE_SIGNATURE)
        ->call('signAndSend')
        ->assertHasErrors('patientSignature');

    expect(Note::count())->toBe(0);
    Mail::assertNothingSent();
});

test('re-evaluation notes are prefilled from the initial evaluation', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $ie = Note::factory()->for($patient)->create([
        'type' => Note::TYPE_IE,
        'data' => ['assessment' => 'Initial assessment', 'plan' => 'Initial plan', 'subjective' => 'Initial subjective', 'blood_pressure' => '', 'heart_rate' => '', 'pain_level' => '', 'objective_findings' => '', 'goals' => '', 'visit_duration_minutes' => '', 'therapist_notes' => ''],
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::patients.notes.create', ['patient' => $patient, 'type' => Note::TYPE_RE]);

    expect($component->get('fields.assessment'))->toBe('Initial assessment');
    expect($component->get('fields.subjective'))->toBe('Initial subjective');
});

test('office can be notified again by resending a signed note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->emailed()->create();

    Storage::disk('local')->put($note->pdf_path, 'fake-pdf-contents');

    Livewire::actingAs($user)
        ->test('pages::patients.notes.show', ['note' => $note])
        ->call('resend');

    Mail::assertSent(NoteReadyForOffice::class, 1);
});

test('the office notification email actually renders', function () {
    // Mail::fake() (used everywhere else in this file) never renders the message body,
    // so it can't catch a broken view/markdown reference — render it for real here.
    $note = Note::factory()->emailed()->create();

    $html = (new NoteReadyForOffice($note))->render();

    expect($html)->toContain($note->patient->name);
});
