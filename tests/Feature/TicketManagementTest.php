<?php

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a super admin can assign a ticket to an eligible system user', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $this->actingAs($superAdmin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => $assignee->getKey()])
        ->assertRedirect()
        ->assertSessionHas('toast');

    $ticket->refresh();

    expect($ticket->assignee_id)->toBe($assignee->getKey())
        ->and($ticket->status)->toBe(Ticket::STATUS_ASSIGNED);

    expect(TicketAssignment::query()->where('ticket_id', $ticket->getKey())->count())->toBe(1);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_ASSIGNED)
        ->exists())->toBeTrue();

    expect($assignee->notifications()->count())->toBe(1);
    expect($client->notifications()->count())->toBe(1);
});

test('reassignment keeps an assignment history and notifies both system users', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_FAMILY]);
    $firstAssignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $secondAssignee = User::factory()->create(['role' => User::ROLE_DEVELOPER]);

    $ticket = Ticket::factory()->assigned($firstAssignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($superAdmin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => $secondAssignee->getKey()])
        ->assertRedirect();

    $ticket->refresh();

    expect($ticket->assignee_id)->toBe($secondAssignee->getKey());
    expect(TicketAssignment::query()->where('ticket_id', $ticket->getKey())->count())->toBe(1);
    expect($ticket->assignments()->count())->toBe(1);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_REASSIGNED)
        ->exists())->toBeTrue();

    expect($secondAssignee->notifications()->count())->toBe(1);
    expect($firstAssignee->notifications()->count())->toBe(1);
    expect($client->notifications()->count())->toBe(1);
});

test('a super admin can unassign a ticket', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->create();

    $this->actingAs($superAdmin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => ''])
        ->assertRedirect();

    $ticket->refresh();

    expect($ticket->assignee_id)->toBeNull()
        ->and($ticket->status)->toBe(Ticket::STATUS_OPEN);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_UNASSIGNED)
        ->exists())->toBeTrue();
});

test('tickets cannot be assigned to client users', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_SHOP]);

    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $this->actingAs($superAdmin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => $otherClient->getKey()])
        ->assertSessionHasErrors('assignee_id');

    expect($ticket->fresh()->assignee_id)->toBeNull();
    expect(TicketAssignment::query()->count())->toBe(0);
});

test('only the super admin may assign tickets', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($admin)->create();

    $this->actingAs($admin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => $assignee->getKey()])
        ->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBe($admin->getKey());
});

test('an assigned system user can change the status and the owner is notified', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->assigned($assignee)->create(['user_id' => $client->getKey()]);

    $this->actingAs($assignee)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
        ->assertRedirect()
        ->assertSessionHas('toast');

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_IN_PROGRESS);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_STATUS_CHANGED)
        ->exists())->toBeTrue();

    expect($client->notifications()->count())->toBe(1);

    $this->actingAs($assignee)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_RESOLVED])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_RESOLVED);
    expect($client->notifications()->count())->toBe(2);
});

test('a system user cannot open a ticket they are not assigned to', function () {
    $staff = User::factory()->create(['role' => User::ROLE_DEVELOPER]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($staff)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
        ->assertNotFound();
});

test('a client cannot force an arbitrary status on their ticket', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $ticket = Ticket::factory()->create(['user_id' => $client->getKey()]);

    $this->actingAs($client)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_RESOLVED])
        ->assertForbidden();

    $this->actingAs($client)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_OPEN);
});

test('the super admin can change the status of any ticket and reopen a closed one', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_SHOP]);
    $ticket = Ticket::factory()->create([
        'user_id' => $client->getKey(),
        'status' => Ticket::STATUS_CLOSED,
    ]);

    $this->actingAs($superAdmin)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_OPEN])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_OPEN);

    $this->actingAs($superAdmin)
        ->post(route('tickets.status.update', $ticket), ['status' => Ticket::STATUS_RESOLVED])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_RESOLVED);
    expect($client->notifications()->count())->toBe(2);
});

test('only the super admin can change the ticket priority', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $ticket = Ticket::factory()->assigned($assignee)->create([
        'user_id' => $client->getKey(),
        'priority' => Ticket::PRIORITY_LOW,
    ]);

    $this->actingAs($assignee)
        ->post(route('tickets.priority.update', $ticket), ['priority' => Ticket::PRIORITY_URGENT])
        ->assertForbidden();

    $this->actingAs($client)
        ->post(route('tickets.priority.update', $ticket), ['priority' => Ticket::PRIORITY_URGENT])
        ->assertForbidden();

    expect($ticket->fresh()->priority)->toBe(Ticket::PRIORITY_LOW);

    $this->actingAs($superAdmin)
        ->post(route('tickets.priority.update', $ticket), ['priority' => Ticket::PRIORITY_URGENT])
        ->assertRedirect();

    expect($ticket->fresh()->priority)->toBe(Ticket::PRIORITY_URGENT);

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_PRIORITY_CHANGED)
        ->exists())->toBeTrue();

    expect($client->notifications()->count())->toBe(1);
});

test('assignment changes are recorded in the admin activity log', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($superAdmin)
        ->post(route('tickets.assignee.update', $ticket), ['assignee_id' => $assignee->getKey()])
        ->assertRedirect();

    $this->assertDatabaseHas('admin_activities', [
        'user_id' => $superAdmin->getKey(),
        'action' => 'ticket_assigned',
        'subject' => $ticket->ticket_number,
    ]);
});

test('the admin dashboard shows real ticket statistics', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    Ticket::factory()->create(['status' => Ticket::STATUS_OPEN]);
    Ticket::factory()->resolved()->create();
    Ticket::factory()->closed()->create();

    $this->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('totalTickets', 3)
        ->assertViewHas('unresolvedTickets', 1);
});
