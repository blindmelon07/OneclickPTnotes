<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a pt assistant is sent to their patient list instead of the hidden pages', function (string $route) {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');

    $this->actingAs($ptAssistant)
        ->get(route($route))
        ->assertRedirect(route('patients.index'));
})->with(['dashboard', 'calendar', 'doctors.index', 'home-health-agencies.index', 'insurance-companies.index']);

test('a pt assistant lands on their patient list', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');

    $this->actingAs($ptAssistant)
        ->get(route('home'))
        ->assertRedirect(route('patients.index'));
});

test('the sidebar hides the pages a pt assistant cannot open', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');

    $this->actingAs($ptAssistant)
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee('Patients')
        ->assertDontSee('Dashboard')
        ->assertDontSee('Calendar')
        ->assertDontSee('HHAs');
});

test('unrestricted roles keep every page', function (string $route) {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)->get(route($route))->assertOk();
})->with(['dashboard', 'calendar', 'doctors.index', 'home-health-agencies.index', 'insurance-companies.index']);

test('a user with no role at all keeps every page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('doctors.index'))->assertOk();
});

test('an admin unticking a restriction restores the page for that role', function () {
    $ptAssistant = User::factory()->create()->assignRole('PT Assistant');

    Role::findByName('PT Assistant')->revokePermissionTo(User::HIDE_CALENDAR);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($ptAssistant->fresh())
        ->get(route('calendar'))
        ->assertOk();
});

test('an admin ticking a restriction hides the page for that role', function () {
    $biller = User::factory()->create()->assignRole('Biller');

    Role::findByName('Biller')->givePermissionTo(User::HIDE_DIRECTORIES);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($biller->fresh())
        ->get(route('doctors.index'))
        ->assertRedirect(route('dashboard'));
});

test('the admin role never receives a restriction from the seeder', function () {
    expect(Role::findByName('admin')->permissions->pluck('name')->intersect(User::RESTRICTIONS))->toBeEmpty();
});
