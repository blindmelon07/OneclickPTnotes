<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows patient roster stats', function () {
    $user = User::factory()->create();
    Patient::factory()->active()->create();
    Patient::factory()->discharged()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertSee(__('Total patients'));
});

test('staff without the users.view permission do not see the administration section', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertDontSee(__('Staff users'));
});

test('admins see the administration section', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()->assertSee(__('Staff users'));
});
