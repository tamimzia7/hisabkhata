<?php

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the support parent menu groups tickets and notifications for system users', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('tickets.index'), false)
        ->assertSee(route('notifications.index'), false);
});

test('the support parent menu groups tickets and notifications for client users', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('tickets.index'), false)
        ->assertSee(route('notifications.index'), false)
        ->assertSee(route('tickets.create'), false);
});

test('the support submenu stays expanded and tickets is active on ticket pages', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($superAdmin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee('open: true', false)
        ->assertSee('aria-current="page"', false);
});

test('the support submenu stays expanded and notifications is active on the notification page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee('open: true', false)
        ->assertSee('aria-current="page"', false);
});

test('the support menu preserves unread ticket and notification badges', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);
    $ticket->messages()->create(['sender_id' => $assignee->getKey(), 'body' => 'We replied to your ticket.']);

    $client->notify(new SystemNotification('Heads up', 'Body', '/dashboard'));

    $response = $this->actingAs($client)->get(route('dashboard'));

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->toContain(route('tickets.index'))
        ->and($html)->toContain(route('notifications.index'))
        ->and($html)->toContain(localize_digits(1));
});

test('guests cannot see the support menu', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
