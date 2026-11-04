<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the global thin font weight rule is present on public and dashboard layouts', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);
});

test('the global thin font weight rule is present on notification and ticket pages', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($superAdmin)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);

    $this->actingAs($superAdmin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('font-weight: 100 !important', false);
});
