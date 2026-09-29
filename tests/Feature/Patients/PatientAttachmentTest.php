<?php

use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('local');
});

test('staff can upload an attachment to a patient chart', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('newAttachmentCategory', PatientAttachment::CATEGORY_REFERRAL)
        ->set('newAttachment', UploadedFile::fake()->create('referral.pdf', 100, 'application/pdf'))
        ->call('uploadAttachment')
        ->assertHasNoErrors();

    $attachment = PatientAttachment::sole();

    expect($attachment->patient_id)->toBe($patient->id);
    expect($attachment->uploaded_by)->toBe($user->id);
    expect($attachment->category)->toBe(PatientAttachment::CATEGORY_REFERRAL);
    expect($attachment->original_filename)->toBe('referral.pdf');

    Storage::disk('local')->assertExists($attachment->path);
});

test('an attachment must be a pdf, jpg, or png', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('newAttachmentCategory', PatientAttachment::CATEGORY_OTHER)
        ->set('newAttachment', UploadedFile::fake()->create('malware.exe', 10))
        ->call('uploadAttachment')
        ->assertHasErrors('newAttachment');

    expect(PatientAttachment::count())->toBe(0);
});

test('downloading an attachment is recorded in the audit log', function () {
    $user = User::factory()->create();
    $attachment = PatientAttachment::factory()->create();

    Storage::disk('local')->put($attachment->path, 'fake-file-contents');

    $this->actingAs($user)
        ->get(route('attachments.download', $attachment))
        ->assertOk();

    $activity = Activity::where('log_name', 'patient')
        ->where('subject_type', PatientAttachment::class)
        ->where('subject_id', $attachment->id)
        ->where('description', 'attachment downloaded')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
});

test('only a user who can manage patients can delete an attachment', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $attachment = PatientAttachment::factory()->for($patient)->create();

    Storage::disk('local')->put($attachment->path, 'fake-file-contents');

    Livewire::actingAs($user)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('deleteAttachment', $attachment->id)
        ->assertForbidden();

    expect(PatientAttachment::query()->whereKey($attachment->id)->exists())->toBeTrue();
});

test('a user who can manage patients can delete an attachment', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create();
    $attachment = PatientAttachment::factory()->for($patient)->create();

    Storage::disk('local')->put($attachment->path, 'fake-file-contents');

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->call('deleteAttachment', $attachment->id);

    expect(PatientAttachment::query()->whereKey($attachment->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($attachment->path);
});
