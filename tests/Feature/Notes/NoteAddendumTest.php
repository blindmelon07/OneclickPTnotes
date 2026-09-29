<?php

use App\Models\Note;
use App\Models\NoteAddendum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('staff can add an addendum to a signed note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->signed()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.show', ['note' => $note])
        ->set('addendum', 'Patient reported improved mobility after the visit.')
        ->call('addAddendum')
        ->assertHasNoErrors();

    $addendum = NoteAddendum::sole();

    expect($addendum->note_id)->toBe($note->id);
    expect($addendum->author_id)->toBe($user->id);
    expect($addendum->content)->toBe('Patient reported improved mobility after the visit.');

    $activity = Activity::where('log_name', 'note')
        ->where('subject_type', NoteAddendum::class)
        ->where('subject_id', $addendum->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
});

test('an addendum requires content', function () {
    $user = User::factory()->create();
    $note = Note::factory()->signed()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.show', ['note' => $note])
        ->set('addendum', '')
        ->call('addAddendum')
        ->assertHasErrors('addendum');

    expect(NoteAddendum::count())->toBe(0);
});

test('an unsigned note cannot receive an addendum', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::patients.notes.show', ['note' => $note])
        ->set('addendum', 'Trying to addend a draft.')
        ->call('addAddendum')
        ->assertForbidden();

    expect(NoteAddendum::count())->toBe(0);
});

test('a signed note cannot be deleted, even by a user who can manage notes', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create()->assignRole('admin');
    $note = Note::factory()->signed()->create();

    Livewire::actingAs($admin)
        ->test('pages::patients.notes.show', ['note' => $note])
        ->call('delete')
        ->assertForbidden();

    expect(Note::query()->whereKey($note->id)->exists())->toBeTrue();
});
