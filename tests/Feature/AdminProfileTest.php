<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('shows the profile page to an admin user', function () {
    $admin = User::factory()->create(['mode' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.profile'));

    $response->assertOk();
    $response->assertSee('অ্যাডমিন প্রোফাইল');
    $response->assertSee($admin->name);
    $response->assertSee($admin->email);
});

test('forbids non-admin users from the profile page', function () {
    $user = User::factory()->create(['mode' => 'student']);

    $response = $this->actingAs($user)->get(route('admin.profile'));

    $response->assertForbidden();
});

test('redirects guests from the profile page to login', function () {
    $response = $this->get(route('admin.profile'));

    $response->assertRedirect(route('login'));
});

test('updates the admin name and email', function () {
    $admin = User::factory()->create(['mode' => 'admin']);

    $response = $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => 'নতুন নাম',
        'email' => 'new-admin@example.com',
    ]);

    $response->assertRedirect(route('admin.profile'));
    $response->assertSessionHas('toast', 'প্রোফাইল সফলভাবে আপডেট হয়েছে!');

    $admin->refresh();

    expect($admin->name)->toBe('নতুন নাম');
    expect($admin->email)->toBe('new-admin@example.com');
});

test('changes the password when the current password is correct', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'password' => 'old-secret-1']);

    $response = $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'old-secret-1',
        'password' => 'new-secret-99',
        'password_confirmation' => 'new-secret-99',
    ]);

    $response->assertRedirect(route('admin.profile'));

    $admin->refresh();

    expect(Hash::check('new-secret-99', $admin->password))->toBeTrue();
    expect(Hash::check('old-secret-1', $admin->password))->toBeFalse();
});

test('rejects a password change with an incorrect current password', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'password' => 'old-secret-1']);

    $response = $this->actingAs($admin)->from(route('admin.profile'))->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'wrong-password',
        'password' => 'new-secret-99',
        'password_confirmation' => 'new-secret-99',
    ]);

    $response->assertSessionHasErrors([
        'current_password' => 'পাসওয়ার্ড পরিবর্তন করতে সঠিক বর্তমান পাসওয়ার্ড লিখুন।',
    ]);

    $admin->refresh();

    expect(Hash::check('old-secret-1', $admin->password))->toBeTrue();
});

test('keeps the password when none is provided', function () {
    $admin = User::factory()->create(['mode' => 'admin', 'password' => 'old-secret-1']);

    $response = $this->actingAs($admin)->put(route('admin.profile.update'), [
        'name' => 'শুধু নাম',
        'email' => $admin->email,
    ]);

    $response->assertRedirect(route('admin.profile'));

    $admin->refresh();

    expect($admin->name)->toBe('শুধু নাম');
    expect(Hash::check('old-secret-1', $admin->password))->toBeTrue();
});

test('rejects an email already used by another account', function () {
    $admin = User::factory()->create(['mode' => 'admin']);
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->actingAs($admin)->from(route('admin.profile'))->put(route('admin.profile.update'), [
        'name' => $admin->name,
        'email' => 'taken@example.com',
    ]);

    $response->assertSessionHasErrors(['email' => 'এই ইমেইল দিয়ে আগে থেকেই অ্যাকাউন্ট আছে।']);
});
