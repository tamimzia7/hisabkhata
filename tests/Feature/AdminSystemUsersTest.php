<?php

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('loads system users and client users from the database', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);
    $staff = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_CUSTOMER_CARE]);
    $client = User::factory()->create(['mode' => 'student']);

    $response = $this->actingAs($admin)->get(route('dashboard', ['page' => 'system-users']));

    $response->assertOk();
    expect($response->viewData('adminPage'))->toBe('system-users');
    expect($response->viewData('systemUsers')->pluck('id')->all())->toBe([$admin->id, $staff->id]);
    expect($response->viewData('clientUsers')->pluck('id')->all())->toBe([$client->id]);
});

test('shows the no-permission notice to a customer care admin', function () {
    $staff = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_CUSTOMER_CARE]);

    $response = $this->actingAs($staff)->get(route('dashboard', ['page' => 'system-users']));

    $response->assertOk();
    $response->assertSee('আপনার নতুন ইউজার তৈরি করার অনুমতি নেই।');
    $response->assertDontSee(route('admin.system-users.store'));
});

test('lets a developer create a customer care system user', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($developer)->post(route('admin.system-users.store'), [
        'name' => 'সাকিব হাসান',
        'email' => 'sakib@example.com',
        'password' => 'secret1234',
        'role' => User::ROLE_CUSTOMER_CARE,
    ]);

    $response->assertRedirect(route('dashboard', ['page' => 'system-users']));
    $response->assertSessionHas('toast', 'সিস্টেম ইউজার সফলভাবে তৈরি হয়েছে!');

    $staff = User::where('email', 'sakib@example.com')->firstOrFail();

    expect($staff->mode)->toBe('admin');
    expect($staff->role)->toBe(User::ROLE_CUSTOMER_CARE);
    expect(Hash::check('secret1234', $staff->password))->toBeTrue();

    expect(AdminActivity::where('user_id', $developer->id)->where('action', 'staff_created')->count())->toBe(1);
});

test('lets a sub admin create a customer care system user', function () {
    $subAdmin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_SUB_ADMIN]);

    $this->actingAs($subAdmin)->post(route('admin.system-users.store'), [
        'name' => 'Support One',
        'email' => 'support@example.com',
        'password' => 'secret1234',
    ])->assertRedirect(route('dashboard', ['page' => 'system-users']));

    expect(User::where('email', 'support@example.com')->value('role'))->toBe(User::ROLE_CUSTOMER_CARE);
});

test('forbids a customer care admin from creating system users', function () {
    $staff = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_CUSTOMER_CARE]);

    $this->actingAs($staff)->post(route('admin.system-users.store'), [
        'name' => 'Nope',
        'email' => 'nope@example.com',
        'password' => 'secret1234',
    ])->assertForbidden();

    expect(User::where('email', 'nope@example.com')->exists())->toBeFalse();
});

test('forbids client users from creating system users', function () {
    $client = User::factory()->create(['mode' => 'student']);

    $this->actingAs($client)->post(route('admin.system-users.store'), [
        'name' => 'Nope',
        'email' => 'nope@example.com',
        'password' => 'secret1234',
    ])->assertForbidden();
});

test('rejects a role higher than customer care', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($developer)->post(route('admin.system-users.store'), [
        'name' => 'Wannabe Dev',
        'email' => 'dev@example.com',
        'password' => 'secret1234',
        'role' => User::ROLE_DEVELOPER,
    ]);

    $response->assertSessionHasErrors(['role' => 'শুধুমাত্র Customer Care অ্যাকাউন্ট তৈরি করা যায়।']);
    expect(User::where('email', 'dev@example.com')->exists())->toBeFalse();
});

test('rejects a duplicate email when creating a system user', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->actingAs($developer)->post(route('admin.system-users.store'), [
        'form' => 'staff',
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertRedirect(route('dashboard', ['page' => 'system-users']));
    $response->assertSessionHasErrors(['email' => 'এই ইমেইল দিয়ে আগে থেকেই অ্যাকাউন্ট আছে।']);
    $response->assertSessionHasInput('form', 'staff');
});

test('removes a customer care system user and logs the action', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);
    $staff = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_CUSTOMER_CARE, 'name' => 'সাকিব']);

    $response = $this->actingAs($developer)->delete(route('admin.system-users.destroy', $staff));

    $response->assertRedirect(route('dashboard', ['page' => 'system-users']));
    $response->assertSessionHas('toast', 'সিস্টেম ইউজার রিমুভ করা হয়েছে।');

    expect(User::find($staff->id))->toBeNull();
    expect(AdminActivity::where('action', 'staff_removed')->where('subject', 'সাকিব')->exists())->toBeTrue();
});

test('does not remove a developer account', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);
    $otherDeveloper = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $this->actingAs($developer)
        ->delete(route('admin.system-users.destroy', $otherDeveloper))
        ->assertForbidden();

    expect(User::find($otherDeveloper->id))->not->toBeNull();
});

test('shows logged admin activity on the activity page', function () {
    $developer = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    AdminActivity::create([
        'user_id' => $developer->id,
        'action' => 'staff_created',
        'subject' => 'নতুন স্টাফ',
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->actingAs($developer)->get(route('dashboard', ['page' => 'system-activity']));

    $response->assertOk();
    $response->assertSee('নতুন স্টাফ');
    $response->assertSee('127.0.0.1');
    expect($response->viewData('activities'))->toHaveCount(1);
});

test('saves the client app settings', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($admin)->put(route('admin.client-settings.update'), [
        'free_trial' => '0',
        'manual_access' => '1',
    ]);

    $response->assertRedirect(route('dashboard', ['page' => 'client-settings']));
    $response->assertSessionHas('toast', 'সেটিংস সফলভাবে সেভ হয়েছে!');

    expect(Setting::boolean('client.free_trial', true))->toBeFalse();
    expect(Setting::boolean('client.manual_access', false))->toBeTrue();
});

test('shows the saved client settings when the panel loads', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);
    Setting::putBoolean('client.free_trial', false);
    Setting::putBoolean('client.manual_access', true);

    $response = $this->actingAs($admin)->get(route('dashboard', ['page' => 'client-settings']));

    $response->assertOk();
    expect($response->viewData('clientSettings'))->toBe([
        'free_trial' => false,
        'manual_access' => true,
    ]);
});

test('forbids client users from saving client app settings', function () {
    $client = User::factory()->create(['mode' => 'personal']);

    $this->actingAs($client)->put(route('admin.client-settings.update'), [
        'free_trial' => '1',
    ])->assertForbidden();
});

test('renders the nested admin navigation', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('সিস্টেম ইউজার');
    $response->assertSee('সিস্টেম ইউজার অ্যাক্টিভিটি');
    $response->assertSee('ক্লায়েন্ট ম্যানেজমেন্ট');
    $response->assertSee('ক্লায়েন্ট সেটিংস');
});

test('renders the panel in english', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($admin)
        ->withSession(['locale' => 'en'])
        ->get(route('dashboard', ['page' => 'client-users']));

    $response->assertOk();
    $response->assertSee('People using the app (student, personal, business).');
});

test('shows the real role of the admin on the profile page', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_SUB_ADMIN]);

    $response = $this->actingAs($admin)->get(route('admin.profile'));

    $response->assertOk();
    $response->assertSee('সাব অ্যাডমিন');
});

test('opens the requested panel page from the query string', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'role' => User::ROLE_DEVELOPER]);

    $response = $this->actingAs($admin)->get(route('dashboard', ['page' => 'client-users']));

    $response->assertOk();
    expect($response->viewData('adminPage'))->toBe('client-users');

    $fallback = $this->actingAs($admin)->get(route('dashboard', ['page' => 'not-a-page']));

    expect($fallback->viewData('adminPage'))->toBe('admin-dashboard');
});
