<?php

use App\Models\User;
use App\Notifications\NotificationService;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every authenticated role can open the notifications page', function () {
    foreach (User::ROLES as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.title'));
    }
});

test('the notification bell and sidebar link render on both dashboards', function () {
    $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('notifications.index'), false)
        ->assertSee(__('notifications.bell_label'));

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('notifications.index'), false)
        ->assertSee(__('notifications.bell_label'));
});

test('the feed returns only the authenticated user notifications', function () {
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $alice->notify(new SystemNotification('Alice only', 'Alice body', '/dashboard'));
    $alice->notify(new SystemNotification('Alice second', 'Alice body 2', '/notifications'));
    $bob->notify(new SystemNotification('Bob only', 'Bob body', '/dashboard'));

    $response = $this->actingAs($alice)->getJson(route('notifications.get'));

    $response->assertOk()
        ->assertJsonPath('unread', 2)
        ->assertJsonCount(2, 'notifications')
        ->assertJsonFragment(['title' => 'Alice only'])
        ->assertJsonFragment(['title' => 'Alice second'])
        ->assertJsonMissing(['title' => 'Bob only']);
});

test('a notification can only be marked read by its owner', function () {
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $alice->notify(new SystemNotification('Alice only', 'Alice body', '/dashboard'));
    $bob->notify(new SystemNotification('Bob only', 'Bob body', '/dashboard'));

    $aliceNotification = $alice->notifications()->firstOrFail();
    $bobNotification = $bob->notifications()->firstOrFail();

    $this->actingAs($alice)
        ->postJson(route('notifications.mark'), ['notification_id' => $bobNotification->id])
        ->assertNotFound();

    expect($bobNotification->fresh()->read_at)->toBeNull();

    $this->actingAs($alice)
        ->postJson(route('notifications.mark'), ['notification_id' => $aliceNotification->id])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('unread', 0);

    expect($aliceNotification->fresh()->read_at)->not->toBeNull();
});

test('mark all read only affects the authenticated user', function () {
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $alice->notify(new SystemNotification('Alice one', 'Body', '/dashboard'));
    $alice->notify(new SystemNotification('Alice two', 'Body', '/dashboard'));
    $bob->notify(new SystemNotification('Bob one', 'Body', '/dashboard'));

    $this->actingAs($alice)
        ->postJson(route('notifications.mark-all-read'))
        ->assertOk()
        ->assertJsonPath('unread', 0);

    expect($alice->unreadNotifications()->count())->toBe(0);
    expect($bob->unreadNotifications()->count())->toBe(1);
});

test('a user can delete only their own notification', function () {
    $alice = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $bob = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $alice->notify(new SystemNotification('Alice only', 'Body', '/dashboard'));
    $bob->notify(new SystemNotification('Bob only', 'Body', '/dashboard'));

    $bobNotification = $bob->notifications()->firstOrFail();

    $this->actingAs($alice)
        ->deleteJson(route('notifications.destroy', $bobNotification->id))
        ->assertNotFound();

    expect($bob->notifications()->count())->toBe(1);

    $aliceNotification = $alice->notifications()->firstOrFail();

    $this->actingAs($alice)
        ->deleteJson(route('notifications.destroy', $aliceNotification->id))
        ->assertOk();

    expect($alice->notifications()->count())->toBe(0);
});

test('opening a notification marks it read and redirects to an internal destination', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $user->notify(new SystemNotification('Open me', 'Body', '/dashboard?page=client-users'));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect('/dashboard?page=client-users');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('opening a notification never redirects to an external URL', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $user->notify(new SystemNotification('Unsafe', 'Body', 'https://example.com/phishing'));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect(route('dashboard'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('the notification page filters unread and read notifications', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $user->notify(new SystemNotification('Unread one', 'Body', '/dashboard'));
    $user->notify(new SystemNotification('Read one', 'Body', '/dashboard'));

    $readNotification = $user->notifications()
        ->get()
        ->first(fn ($notification): bool => ($notification->data['title'] ?? null) === 'Read one');

    $readNotification->markAsRead();

    $this->actingAs($user)
        ->get(route('notifications.index', ['filter' => 'unread']))
        ->assertOk()
        ->assertSee('Unread one')
        ->assertDontSee('Read one');

    $this->actingAs($user)
        ->get(route('notifications.index', ['filter' => 'read']))
        ->assertOk()
        ->assertSee('Read one')
        ->assertDontSee('Unread one');
});

test('guests cannot access notification routes', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->getJson(route('notifications.get'))->assertUnauthorized();
});

test('notifications persist after logout and login', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_STUDENT,
        'password' => 'secret1234',
    ]);

    $user->notify(new SystemNotification('Persistent notification', 'Body', '/dashboard'));

    $this->actingAs($user)
        ->delete(route('logout'))
        ->assertRedirect(route('home'));

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret1234',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Persistent notification');
});

test('the notification service can deliver to one user, many users and roles', function () {
    $service = app(NotificationService::class);

    $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $family = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $service->sendToUser($student, 'Single', 'Body');
    expect($student->notifications()->count())->toBe(1);

    $service->sendToUsers([$student, $family], 'Pair', 'Body');
    expect($student->notifications()->count())->toBe(2);
    expect($family->notifications()->count())->toBe(1);

    $service->sendToRoles([User::ROLE_ADMIN], 'Admins', 'Body');
    expect($admin->notifications()->count())->toBe(1);
    expect($family->notifications()->count())->toBe(1);
});
