<?php

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login from the ticket pages', function () {
    $ticket = Ticket::factory()->create();

    $this->get(route('tickets.index'))->assertRedirect(route('login'));
    $this->get(route('tickets.create'))->assertRedirect(route('login'));
    $this->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
});

test('a client can create a ticket', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $response = $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => 'Cannot log in to my account',
        'category' => Ticket::CATEGORY_ACCOUNT_ISSUE,
        'priority' => Ticket::PRIORITY_HIGH,
        'description' => 'I have not been able to log in since yesterday.',
    ]);

    $ticket = Ticket::query()->firstOrFail();

    $response
        ->assertRedirect(route('tickets.show', $ticket))
        ->assertSessionHas('toast');

    expect($ticket->ticket_number)->toBe('HK-TKT-000001')
        ->and($ticket->status)->toBe(Ticket::STATUS_OPEN)
        ->and($ticket->priority)->toBe(Ticket::PRIORITY_HIGH)
        ->and($ticket->category)->toBe(Ticket::CATEGORY_ACCOUNT_ISSUE)
        ->and($ticket->user_id)->toBe($client->getKey())
        ->and($ticket->assignee_id)->toBeNull();

    expect(TicketActivity::query()
        ->where('ticket_id', $ticket->getKey())
        ->where('action', TicketActivity::ACTION_CREATED)
        ->exists())->toBeTrue();

    expect($superAdmin->notifications()->count())->toBe(1);
});

test('ticket numbers are generated sequentially in the required format', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    foreach (['First ticket', 'Second ticket', 'Third ticket'] as $subject) {
        $this->actingAs($client)->post(route('tickets.store'), [
            'subject' => $subject,
            'category' => Ticket::CATEGORY_OTHER,
            'priority' => Ticket::PRIORITY_LOW,
            'description' => 'Description for '.$subject,
        ])->assertRedirect();
    }

    expect(Ticket::query()->orderBy('id')->pluck('ticket_number')->all())
        ->toBe(['HK-TKT-000001', 'HK-TKT-000002', 'HK-TKT-000003']);
});

test('system users cannot create tickets', function () {
    foreach (User::SYSTEM_ROLES as $role) {
        $staff = User::factory()->create(['role' => $role]);

        $this->actingAs($staff)->get(route('tickets.create'))->assertForbidden();

        $this->actingAs($staff)->post(route('tickets.store'), [
            'subject' => 'Staff ticket',
            'category' => Ticket::CATEGORY_TECHNICAL_ISSUE,
            'priority' => Ticket::PRIORITY_URGENT,
            'description' => 'System users must never create tickets.',
        ])->assertForbidden();
    }

    expect(Ticket::query()->count())->toBe(0);
});

test('ticket creation validates the required fields', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($client)->post(route('tickets.store'), [
        'subject' => '',
        'category' => 'not-a-category',
        'priority' => 'not-a-priority',
        'description' => '',
    ])->assertSessionHasErrors(['subject', 'category', 'priority', 'description']);

    expect(Ticket::query()->count())->toBe(0);
});

test('a client cannot open another clients ticket', function () {
    $owner = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $otherClient = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $ticket = Ticket::factory()->create(['user_id' => $owner->getKey()]);

    $this->actingAs($otherClient)
        ->get(route('tickets.show', $ticket))
        ->assertNotFound();
});

test('a system user can only open tickets assigned to them', function () {
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $otherStaff = User::factory()->create(['role' => User::ROLE_DEVELOPER]);

    $ticket = Ticket::factory()->assigned($assignee)->create();

    $this->actingAs($otherStaff)
        ->get(route('tickets.show', $ticket))
        ->assertNotFound();

    $this->actingAs($assignee)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->ticket_number);
});

test('a super admin can open every ticket', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($superAdmin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->subject);
});

test('the ticket list only shows tickets the user is allowed to see', function () {
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $assignee = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $aliceTicket = Ticket::factory()->create([
        'user_id' => $alice->getKey(),
        'ticket_number' => 'HK-TKT-900001',
    ]);

    $bobTicket = Ticket::factory()->create([
        'user_id' => $bob->getKey(),
        'ticket_number' => 'HK-TKT-900002',
    ]);

    $assignedTicket = Ticket::factory()->assigned($assignee)->create([
        'user_id' => $bob->getKey(),
        'ticket_number' => 'HK-TKT-900003',
    ]);

    $this->actingAs($alice)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($aliceTicket->ticket_number)
        ->assertDontSee($bobTicket->ticket_number);

    $this->actingAs($assignee)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($assignedTicket->ticket_number)
        ->assertDontSee($aliceTicket->ticket_number);

    $this->actingAs($superAdmin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($aliceTicket->ticket_number)
        ->assertSee($bobTicket->ticket_number)
        ->assertSee($assignedTicket->ticket_number);
});

test('the ticket list supports search and status filters', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    Ticket::factory()->create([
        'ticket_number' => 'HK-TKT-910001',
        'subject' => 'Payment gateway failing',
        'status' => Ticket::STATUS_OPEN,
    ]);

    Ticket::factory()->create([
        'ticket_number' => 'HK-TKT-910002',
        'subject' => 'Invoice layout request',
        'status' => Ticket::STATUS_RESOLVED,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('tickets.index', ['q' => 'payment gateway']))
        ->assertOk()
        ->assertSee('Payment gateway failing')
        ->assertDontSee('Invoice layout request');

    $this->actingAs($superAdmin)
        ->get(route('tickets.index', ['status' => Ticket::STATUS_RESOLVED]))
        ->assertOk()
        ->assertSee('Invoice layout request')
        ->assertDontSee('Payment gateway failing');
});

test('the sidebar exposes the tickets menu for every role', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $staff = User::factory()->create(['role' => User::ROLE_DEVELOPER]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $this->actingAs($client)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('tickets.index'), false)
        ->assertDontSee(route('tickets.create'), false);

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('tickets.index'), false);

    $this->actingAs($superAdmin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('tickets.index'), false)
        ->assertSee(route('notifications.index'), false);
});
