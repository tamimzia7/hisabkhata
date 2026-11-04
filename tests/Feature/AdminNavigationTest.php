<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the admin dashboard is rendered as the in-page SPA', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('dashboard', ['page' => 'client-users']))
        ->assertOk()
        ->assertViewHas('adminSpa', true)
        ->assertSee('spa: true', false);
});

test('the notification page is rendered as a standalone admin page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertViewHas('adminPage', 'notifications')
        ->assertSee('spa: false', false)
        ->assertSee('window.location.assign', false);
});

test('the ticket pages are rendered as standalone admin pages', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($superAdmin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('spa: false', false);

    $this->actingAs($superAdmin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('spa: false', false);
});

test('standalone admin pages keep the existing sidebar and notification links', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee("setPage('client-users')", false)
        ->assertSee(route('notifications.index'), false)
        ->assertSee(__('site.admin.client_users'))
        ->assertSee(__('site.admin.logout'));
});
