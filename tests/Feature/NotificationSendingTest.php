<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an admin can send a notification to all users', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Scheduled maintenance',
        'body' => 'The app will be down tonight.',
        'recipients' => 'all',
    ])->assertRedirect(route('notifications.index'))->assertSessionHas('toast');

    foreach ([$admin, $client, $staff, $superAdmin] as $recipient) {
        expect($recipient->notifications()->count())->toBe(1);

        $data = $recipient->notifications()->first()->data;

        expect($data['title'])->toBe('Scheduled maintenance')
            ->and($data['body'])->toBe('The app will be down tonight.')
            ->and($data['sender_name'])->toBe($admin->name)
            ->and($data['sender_id'])->toBe($admin->getKey());
    }

    expect(Notification::query()->count())->toBe(4);
});

test('a super admin can send to selected users without duplicates', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $carol = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($superAdmin)->post(route('notifications.send'), [
        'title' => 'Selected recipients',
        'body' => 'Only some of you get this.',
        'recipients' => 'selected',
        'recipient_ids' => [$alice->getKey(), $bob->getKey(), $alice->getKey()],
    ])->assertRedirect();

    expect($alice->notifications()->count())->toBe(1)
        ->and($bob->notifications()->count())->toBe(1)
        ->and($carol->notifications()->count())->toBe(0)
        ->and($superAdmin->notifications()->count())->toBe(0)
        ->and(Notification::query()->count())->toBe(2);
});

test('only admins and super admins can send notifications', function () {
    foreach ([
        User::ROLE_DEVELOPER,
        User::ROLE_CUSTOMER_CARE,
        User::ROLE_STUDENT,
    ] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->post(route('notifications.send'), [
            'title' => 'Unauthorized',
            'body' => 'This must never be delivered.',
            'recipients' => 'all',
        ])->assertForbidden();
    }

    expect(Notification::query()->count())->toBe(0);
});

test('notification sending validates its input', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post(route('notifications.send'), [])
        ->assertSessionHasErrors(['title', 'body', 'recipients']);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Missing recipients',
        'body' => 'Body',
        'recipients' => 'selected',
    ])->assertSessionHasErrors('recipient_ids');

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Invalid user',
        'body' => 'Body',
        'recipients' => 'selected',
        'recipient_ids' => [999999],
    ])->assertSessionHasErrors('recipient_ids.0');

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Unsafe link',
        'body' => 'Body',
        'recipients' => 'all',
        'action_url' => 'https://example.com/phishing',
    ])->assertSessionHasErrors('action_url');

    expect(Notification::query()->count())->toBe(0);
});

test('the optional action url must be internal and opens after clicking', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Open the client list',
        'body' => 'Click this notification to open the page.',
        'recipients' => 'selected',
        'recipient_ids' => [$client->getKey()],
        'action_url' => '/dashboard?page=client-users',
    ])->assertRedirect();

    $notification = $client->notifications()->firstOrFail();

    expect($notification->data['action_url'])->toBe('/dashboard?page=client-users');

    $this->actingAs($client)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect('/dashboard?page=client-users');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('the send notification button is only available to admins and super admins', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $developer = User::factory()->create(['role' => User::ROLE_DEVELOPER]);
    $customerCare = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    foreach ([$admin, $superAdmin] as $sender) {
        $this->actingAs($sender)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.send_button'))
            ->assertSee(route('notifications.create'), false);
    }

    foreach ([$client, $developer, $customerCare] as $other) {
        $this->actingAs($other)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee(__('notifications.send_button'))
            ->assertDontSee(route('notifications.create'), false);
    }
});

test('admins and super admins can open the full-page notification form', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT, 'name' => 'Pickable Client']);

    foreach ([$admin, $superAdmin] as $sender) {
        $this->actingAs($sender)
            ->get(route('notifications.create'))
            ->assertOk()
            ->assertSee(__('notifications.create_title'))
            ->assertSee(__('notifications.title'))
            ->assertSee(route('notifications.index'), false)
            ->assertSee(route('notifications.send'), false)
            ->assertSee(__('notifications.send_field_title'))
            ->assertSee(__('notifications.send_field_message'))
            ->assertSee(__('notifications.send_recipients_all'))
            ->assertSee(__('notifications.send_recipients_selected'))
            ->assertSee(__('notifications.cancel'))
            ->assertSee('Pickable Client')
            ->assertSee(__('site.admin.logout'));
    }
});

test('other users cannot open the notification creation page', function () {
    foreach ([
        User::ROLE_STUDENT,
        User::ROLE_DEVELOPER,
        User::ROLE_CUSTOMER_CARE,
    ] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('notifications.create'))
            ->assertForbidden();
    }
});

test('validation errors return to the full-page notification form', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->from(route('notifications.create'))
        ->post(route('notifications.send'), [])
        ->assertRedirect(route('notifications.create'))
        ->assertSessionHasErrors(['title', 'body', 'recipients']);

    expect(Notification::query()->count())->toBe(0);
});

test('sent notifications appear with sender details and update the unread counter', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'New feature available',
        'body' => 'Check out the new invoice export.',
        'recipients' => 'selected',
        'recipient_ids' => [$client->getKey()],
    ])->assertRedirect();

    $this->actingAs($client)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('New feature available')
        ->assertSee('Check out the new invoice export.')
        ->assertSee(__('notifications.sent_by', ['name' => $admin->name]));

    $this->actingAs($client)
        ->getJson(route('notifications.get'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonFragment(['sender_name' => $admin->name]);
});

test('guests cannot send notifications', function () {
    $this->post(route('notifications.send'), [
        'title' => 'Guest',
        'body' => 'Should fail.',
        'recipients' => 'all',
    ])->assertRedirect(route('login'));

    expect(Notification::query()->count())->toBe(0);
});

test('an admin can send a notification to a single dashboard role', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $anotherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'Students only',
        'body' => 'This is for the student dashboard.',
        'recipients' => 'role',
        'roles' => [User::ROLE_STUDENT],
    ])->assertRedirect(route('notifications.index'))->assertSessionHas('toast');

    expect($student->notifications()->count())->toBe(1)
        ->and($anotherStudent->notifications()->count())->toBe(1)
        ->and($admin->notifications()->count())->toBe(0)
        ->and(Notification::query()->count())->toBe(2);
});

test('a super admin can send a notification to a dashboard role without duplicates', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $anotherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($superAdmin)->post(route('notifications.send'), [
        'title' => 'Students only',
        'body' => 'This is for the student dashboard.',
        'recipients' => 'role',
        'roles' => [User::ROLE_STUDENT, User::ROLE_STUDENT],
    ])->assertRedirect(route('notifications.index'));

    expect($student->notifications()->count())->toBe(1)
        ->and($anotherStudent->notifications()->count())->toBe(1)
        ->and($superAdmin->notifications()->count())->toBe(0)
        ->and(Notification::query()->count())->toBe(2);
});

test('role targeting validates the selected dashboards', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'No dashboard',
        'body' => 'Body',
        'recipients' => 'role',
    ])->assertSessionHasErrors('roles');

    $this->actingAs($admin)->post(route('notifications.send'), [
        'title' => 'System role',
        'body' => 'Body',
        'recipients' => 'role',
        'roles' => [User::ROLE_ADMIN],
    ])->assertSessionHasErrors('roles.0');

    expect(Notification::query()->count())->toBe(0);
});

test('the notification form offers the dashboard role section', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('notifications.create'))
        ->assertOk()
        ->assertSee(__('notifications.send_recipients_role'))
        ->assertSee(__('notifications.send_roles_label'))
        ->assertSee('value="'.User::ROLE_STUDENT.'"', false);
});
