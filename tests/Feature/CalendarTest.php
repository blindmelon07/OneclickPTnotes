<?php

use App\Models\Patient;
use App\Models\PatientColor;
use App\Models\User;
use App\Models\Visit;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('calendar'))->assertRedirect(route('login'));
});

test('authenticated users can view the calendar page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar'))
        ->assertOk();
});

test('the calendar shows only the logged-in therapist\'s own visits', function () {
    $user = User::factory()->create();
    $otherTherapist = User::factory()->create();
    $patient = Patient::factory()->create();

    $myVisit = Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $user->id,
        'scheduled_at' => '2026-08-19 10:00:00',
    ]);

    Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $otherTherapist->id,
        'scheduled_at' => '2026-08-19 11:00:00',
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08');

    $weeks = $component->get('weeks');
    $visitsOnAug19 = collect($weeks)
        ->flatten(1)
        ->firstWhere(fn ($day) => $day['date']->format('Y-m-d') === '2026-08-19')['visits'];

    expect($visitsOnAug19)->toHaveCount(1);
    expect($visitsOnAug19->first()->is($myVisit))->toBeTrue();
});

test('previous and next month navigation changes the displayed month', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08');

    $component->call('nextMonth');
    expect($component->get('month'))->toBe('2026-09');

    $component->call('previousMonth');
    expect($component->get('month'))->toBe('2026-08');
});

test('a user can change their own calendar accent color', function () {
    $user = User::factory()->create(['calendar_color' => 'red']);

    Livewire::actingAs($user)
        ->test('pages::calendar')
        ->call('setColor', 'blue')
        ->assertOk();

    expect($user->fresh()->calendar_color)->toBe('blue');
});

test('an invalid calendar color is rejected', function () {
    $user = User::factory()->create(['calendar_color' => 'red']);

    Livewire::actingAs($user)
        ->test('pages::calendar')
        ->call('setColor', 'not-a-real-color')
        ->assertStatus(422);

    expect($user->fresh()->calendar_color)->toBe('red');
});

test('a user can assign their own color to a patient', function () {
    $user = User::factory()->create(['calendar_color' => 'red']);
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::calendar')
        ->call('setPatientColor', $patient->id, 'purple')
        ->assertOk();

    expect(PatientColor::where(['user_id' => $user->id, 'patient_id' => $patient->id])->value('color'))
        ->toBe('purple');
});

test('assigning a patient color again updates it instead of duplicating', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $component = Livewire::actingAs($user)->test('pages::calendar');
    $component->call('setPatientColor', $patient->id, 'purple');
    $component->call('setPatientColor', $patient->id, 'green');

    expect(PatientColor::where(['user_id' => $user->id, 'patient_id' => $patient->id])->count())->toBe(1);
    expect(PatientColor::where(['user_id' => $user->id, 'patient_id' => $patient->id])->value('color'))
        ->toBe('green');
});

test('a patient color is personal to the viewer and falls back to their default otherwise', function () {
    $viewer = User::factory()->create(['calendar_color' => 'blue']);
    $otherUser = User::factory()->create(['calendar_color' => 'green']);
    $coloredPatient = Patient::factory()->create();
    $uncoloredPatient = Patient::factory()->create();

    PatientColor::factory()->create([
        'user_id' => $otherUser->id,
        'patient_id' => $coloredPatient->id,
        'color' => 'pink',
    ]);

    $component = Livewire::actingAs($viewer)->test('pages::calendar');

    // The other user's color for that patient doesn't leak into this viewer's calendar.
    expect($component->instance()->colorForPatient($coloredPatient->id))->toBe('blue');
    expect($component->instance()->colorForPatient($uncoloredPatient->id))->toBe('blue');
});

test('an invalid patient color is rejected', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::calendar')
        ->call('setPatientColor', $patient->id, 'not-a-real-color')
        ->assertStatus(422);

    expect(PatientColor::where(['user_id' => $user->id, 'patient_id' => $patient->id])->exists())->toBeFalse();
});

test('the patient colors legend is paginated', function () {
    $user = User::factory()->create();

    // 7 distinct patients, alphabetically named so ordering is predictable.
    $names = ['Adams', 'Baker', 'Carter', 'Dawson', 'Ellis', 'Foster', 'Grant'];

    foreach ($names as $name) {
        $patient = Patient::factory()->create(['name' => $name]);

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'therapist_id' => $user->id,
            'scheduled_at' => '2026-08-19 10:00:00',
        ]);
    }

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08');

    $page1 = $component->get('patients');
    expect($page1->total())->toBe(7);
    expect($page1->count())->toBe(5);
    expect($page1->pluck('name')->all())->toBe(['Adams', 'Baker', 'Carter', 'Dawson', 'Ellis']);

    $component->call('gotoPage', 2);

    $page2 = $component->get('patients');
    expect($page2->count())->toBe(2);
    expect($page2->pluck('name')->all())->toBe(['Foster', 'Grant']);
});

test('viewing a day opens the mobile day-visits modal with that day\'s visits', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create(['name' => 'Mobile Test Patient']);

    Visit::factory()->create([
        'patient_id' => $patient->id,
        'therapist_id' => $user->id,
        'scheduled_at' => '2026-08-19 10:00:00',
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08')
        ->call('viewDay', '2026-08-19');

    expect($component->get('selectedDay'))->toBe('2026-08-19');

    $visits = $component->get('selectedDayVisits');
    expect($visits)->toHaveCount(1);
    expect($visits->first()->patient->is($patient))->toBeTrue();
});

test('the mobile day-visits modal is empty for a day with no visits', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08')
        ->call('viewDay', '2026-08-20');

    expect($component->get('selectedDayVisits'))->toHaveCount(0);
});

test('navigating months resets the patient colors legend to page one', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $i) {
        $patient = Patient::factory()->create(['name' => "Patient {$i}"]);

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'therapist_id' => $user->id,
            'scheduled_at' => '2026-08-19 10:00:00',
        ]);
    }

    $component = Livewire::actingAs($user)
        ->test('pages::calendar')
        ->set('month', '2026-08')
        ->call('gotoPage', 2);

    expect($component->get('patients')->currentPage())->toBe(2);

    $component->call('nextMonth');

    expect($component->get('patients')->currentPage())->toBe(1);
});
