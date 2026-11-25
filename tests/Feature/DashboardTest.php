<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users are sent to their area', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect('/school');
});

test('students are sent to their own page', function () {
    $user = User::factory()->create(['role' => 'student']);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect('/my-attendance');
});