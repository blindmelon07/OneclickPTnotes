<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the number of visits can be set when creating a patient', function () {
    $admin = User::factory()->create()->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::patients.index')
        ->set('name', 'Bryan')
        ->set('approved_visits', 5)
        ->set('status', Patient::STATUS_ACTIVE)
        ->call('createPatient')
        ->assertHasNoErrors();

    expect(Patient::where('name', 'Bryan')->first()->approved_visits)->toBe(5);
});

test('the admin takes the first and last visit and the pt assistant the rest', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create([
        'approved_visits' => 5,
        'pt_assistant_id' => $ptAssistant->id,
    ]);

    $component = Livewire::actingAs($admin)->test('pages::patients.show', ['patient' => $patient]);

    foreach (range(1, 5) as $sequence) {
        $component
            ->set('visitScheduledAt', now()->addDays($sequence)->format('Y-m-d\TH:i'))
            ->call('scheduleVisit')
            ->assertHasNoErrors();
    }

    $therapists = $patient->visits()->orderBy('scheduled_at')->pluck('therapist_id')->all();

    expect($therapists)->toBe([
        $admin->id,
        $ptAssistant->id,
        $ptAssistant->id,
        $ptAssistant->id,
        $admin->id,
    ]);
});

test('a single approved visit belongs to the admin, being both first and last', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create([
        'approved_visits' => 1,
        'pt_assistant_id' => $ptAssistant->id,
    ]);

    expect($patient->therapistForVisitNumber(1)->id)->toBe($admin->id);
});

test('with no visit count set only the first visit falls to the admin', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create([
        'approved_visits' => null,
        'pt_assistant_id' => $ptAssistant->id,
    ]);

    expect($patient->therapistForVisitNumber(1)->id)->toBe($admin->id);
    expect($patient->therapistForVisitNumber(2)->id)->toBe($ptAssistant->id);
    expect($patient->therapistForVisitNumber(99)->id)->toBe($ptAssistant->id);
});

test('a patient with no pt assistant falls back to the scheduling user for middle visits', function () {
    User::factory()->create()->assignRole('admin');
    $scheduler = User::factory()->create()->assignRole('staff');
    $patient = Patient::factory()->create(['approved_visits' => 3, 'pt_assistant_id' => null]);

    $patient->visits()->create([
        'therapist_id' => $scheduler->id,
        'visit_type' => 'fu',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($scheduler)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('visitScheduledAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('scheduleVisit')
        ->assertHasNoErrors();

    expect($patient->visits()->orderBy('scheduled_at')->pluck('therapist_id')->last())->toBe($scheduler->id);
});

test('the patient page shows the visit counts', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');
    $patient = Patient::factory()->create([
        'approved_visits' => 5,
        'pt_assistant_id' => $ptAssistant->id,
    ]);

    $component = Livewire::actingAs($admin)->test('pages::patients.show', ['patient' => $patient]);

    $component->assertSet('visitProgress.approved', 5)
        ->assertSet('visitProgress.scheduled', 0)
        ->assertSet('visitProgress.remaining', 5);

    $component
        ->set('visitScheduledAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('scheduleVisit')
        ->assertSet('visitProgress.scheduled', 1)
        ->assertSet('visitProgress.remaining', 4);
});

test('scheduling past the approved count reports zero remaining', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create(['approved_visits' => 1]);

    Livewire::actingAs($admin)
        ->test('pages::patients.show', ['patient' => $patient])
        ->set('visitScheduledAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('scheduleVisit')
        ->set('visitScheduledAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('scheduleVisit')
        ->assertSet('visitProgress.scheduled', 2)
        ->assertSet('visitProgress.remaining', 0)
        ->assertSee('Over approved count');
});

test('each upcoming visit is numbered within the course', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $patient = Patient::factory()->create(['approved_visits' => 3]);

    $component = Livewire::actingAs($admin)->test('pages::patients.show', ['patient' => $patient]);

    foreach (range(1, 3) as $day) {
        $component->set('visitScheduledAt', now()->addDays($day)->format('Y-m-d\TH:i'))->call('scheduleVisit');
    }

    expect(array_values($component->get('visitNumbers')))->toBe([1, 2, 3]);
});
