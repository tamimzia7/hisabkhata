<?php

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the assigned system user and super admin are notified about a client reply', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Any update on this issue?'])
        ->assertRedirect(route('tickets.show', $ticket))
        ->assertSessionHas('toast');

    expect($ticket->fresh()->messages()->count())->toBe(1);
    expect($ticket->fresh()->last_reply_at)->not->toBeNull();

    expect($assignee->notifications()->count())->toBe(1);
    expect($superAdmin->notifications()->count())->toBe(1);
    expect($client->notifications()->count())->toBe(0);
});

test('the ticket owner is notified when the assigned system user replies', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($assignee)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'We are looking into it.'])
        ->assertRedirect(route('tickets.show', $ticket));

    expect($ticket->fresh()->messages()->count())->toBe(1);
    expect($client->notifications()->count())->toBe(1);
    expect($assignee->notifications()->count())->toBe(0);
});

test('only the super admin is notified when an unassigned ticket receives a client reply', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Additional details.'])
        ->assertRedirect();

    expect($superAdmin->notifications()->count())->toBe(1);
});

test('users without access cannot post messages', function () {
    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $unassignedStaff = User::factory()->create(['role' => User::ROLE_DEVELOPER]);

    $ticket = Ticket::factory()->create(['user_id' => $owner->getKey()]);

    $this->actingAs($otherClient)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Hijack'])
        ->assertNotFound();

    $this->actingAs($unassignedStaff)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Hijack'])
        ->assertNotFound();

    expect($ticket->fresh()->messages()->count())->toBe(0);
});

test('empty messages are rejected', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), ['body' => '   '])
        ->assertSessionHasErrors('body');

    expect($ticket->fresh()->messages()->count())->toBe(0);
});

test('message bodies are escaped in the rendered conversation', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $payload = $this->actingAs($client)
        ->postJson(route('tickets.messages.store', $ticket), ['body' => '<script>alert("xss")</script>'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->json();

    expect($payload['html'])->toContain('&lt;script&gt;')
        ->and($payload['html'])->not->toContain('<script>alert');

    expect($ticket->fresh()->messages()->firstOrFail()->body)
        ->toBe('<script>alert("xss")</script>');
});

test('closed tickets reject new messages until they are reopened', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->closed()->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Still broken!'])
        ->assertSessionHasErrors('body');

    expect($ticket->fresh()->messages()->count())->toBe(0);

    $this->actingAs($assignee)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'Reopening this.'])
        ->assertSessionHasErrors('body');

    expect($ticket->fresh()->messages()->count())->toBe(0);
});

test('a client can close an active ticket and request a reopen', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_CLOSED])
        ->assertRedirect()
        ->assertSessionHas('toast');

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED);
    expect($assignee->notifications()->count())->toBe(1);
    expect($superAdmin->notifications()->count())->toBe(1);

    $this->actingAs($client)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_OPEN])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_OPEN);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_REOPEN_REQUESTED)
        ->exists())->toBeTrue();
});

test('a client reply reopens a resolved ticket', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->resolved()->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.messages.store', $ticket), ['body' => 'The issue came back.'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_OPEN);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_REOPENED)
        ->exists())->toBeTrue();
});

test('the conversation polling endpoint only returns new messages and marks them read', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $first = $ticket->messages()->create(['sender_id' => $client->getKey(), 'body' => 'First message']);
    $second = $ticket->messages()->create(['sender_id' => $assignee->getKey(), 'body' => 'Second message']);

    expect(Ticket::unreadTicketCountFor($client))->toBe(1);

    $payload = $this->actingAs($client)
        ->getJson(route('tickets.messages.fetch', $ticket))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->json();

    expect($payload['html'])->toContain('First message')
        ->and($payload['html'])->toContain('Second message')
        ->and($payload['last_id'])->toBe($second->getKey());

    expect(Ticket::unreadTicketCountFor($client))->toBe(0);

    $newPayload = $this->actingAs($client)
        ->getJson(route('tickets.messages.fetch', ['ticket' => $ticket, 'after' => $second->getKey()]))
        ->assertOk()
        ->json();

    expect($newPayload['html'])->toBe('')
        ->and($newPayload['last_id'])->toBe($second->getKey());
});

test('the polling endpoint rejects users who cannot view the ticket', function () {
    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $ticket = Ticket::factory()->create(['user_id' => $owner->getKey()]);

    $this->actingAs($otherClient)
        ->getJson(route('tickets.messages.fetch', $ticket))
        ->assertNotFound();
});

test('opening a conversation marks it read for that user only', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $ticket->messages()->create(['sender_id' => $client->getKey(), 'body' => 'Hello team']);

    expect(Ticket::unreadTicketCountFor($superAdmin))->toBe(1);
    expect(Ticket::unreadTicketCountFor($client))->toBe(0);

    $this->actingAs($superAdmin)->get(route('tickets.show', $ticket))->assertOk();

    expect(Ticket::unreadTicketCountFor($superAdmin))->toBe(0);
    expect(Ticket::unreadTicketCountFor($client))->toBe(0);
});

test('ticket notifications link directly to the conversation', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Notification link test',
        'category' => Ticket::CATEGORY_TECHNICAL_ISSUE,
        'priority' => Ticket::PRIORITY_MEDIUM,
        'description' => 'Check the notification destination.',
    ])->assertRedirect();

    $ticket = Ticket::query()->firstOrFail();
    $notification = $superAdmin->notifications()->firstOrFail();

    expect($notification->data['action_url'])
        ->toBe(route('tickets.show', $ticket, absolute: false));

    $this->actingAs($superAdmin)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect(route('tickets.show', $ticket));

    expect($notification->fresh()->read_at)->not->toBeNull();
});
