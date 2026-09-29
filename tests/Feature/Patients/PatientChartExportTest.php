<?php

use App\Models\Note;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('local');
});

test('a visible patient chart can be downloaded as one PDF', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['name' => 'Jane Chart']);

    Note::factory()->signed()->for($patient)->create([
        'data' => ['assessment' => 'Chart export assessment text', 'plan' => 'Chart export plan'],
    ]);

    PatientAttachment::factory()->for($patient)->create(['original_filename' => 'referral.pdf']);

    $response = $this->actingAs($user)->get(route('patients.chart.download', $patient));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $activity = Activity::where('log_name', 'patient')
        ->where('subject_type', Patient::class)
        ->where('subject_id', $patient->id)
        ->where('description', 'chart exported')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
});

test('a caseload-restricted PT Assistant cannot export a chart for a patient assigned to someone else', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $assistant = User::factory()->create()->assignRole('PT Assistant');
    $someoneElse = User::factory()->create();
    $patient = Patient::factory()->create(['pt_assistant_id' => $someoneElse->id]);

    $this->actingAs($assistant)
        ->get(route('patients.chart.download', $patient))
        ->assertForbidden();
});
