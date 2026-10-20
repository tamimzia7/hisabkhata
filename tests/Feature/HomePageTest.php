<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders the home page for guests', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('হিসাবখাতা');
    $response->assertSee(route('login'));
    $response->assertSee(route('register'));
});

test('links an authenticated user to the dashboard from the home page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $response->assertSee(route('dashboard'));
    $response->assertDontSee(route('login'));
});
