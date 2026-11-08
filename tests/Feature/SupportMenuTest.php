<?php

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Determine whether the given link is nested inside a sidebar submenu.
 */
function linkIsInsideSidebarSubmenu(string $html, string $href): bool
{
    $dom = new DOMDocument;

    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $links = (new DOMXPath($dom))->query('//a[@href="'.$href.'"]');

    if ($links->length === 0) {
        return false;
    }

    for ($node = $links->item(0)->parentNode; $node !== null; $node = $node->parentNode) {
        if ($node instanceof DOMElement && str_contains((string) $node->getAttribute('class'), 'nav-sublist')) {
            return true;
        }
    }

    return false;
}

test('support contains only tickets while notifications stays top-level for system users', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'));

    $html = $response->getContent();

    expect(linkIsInsideSidebarSubmenu($html, route('tickets.index')))->toBeTrue()
        ->and(linkIsInsideSidebarSubmenu($html, route('notifications.index')))->toBeFalse();
});

test('support contains only tickets while notifications stays top-level for client users', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertDontSee(route('tickets.create'), false);

    $html = $response->getContent();

    expect(linkIsInsideSidebarSubmenu($html, route('tickets.index')))->toBeTrue()
        ->and(linkIsInsideSidebarSubmenu($html, route('notifications.index')))->toBeFalse();
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

test('the support submenu stays collapsed and notifications is active on the notification page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee(__('tickets.support'))
        ->assertSee(route('notifications.index'), false)
        ->assertDontSee('open: true', false)
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
