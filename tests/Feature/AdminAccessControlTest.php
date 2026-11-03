<?php

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders the developer access and role permission child menus', function () {
    $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('ডেভেলপার অ্যাক্সেস কন্ট্রোল');
    $response->assertSee('রোল ও পারমিশন');
});

test('opens the developer access page for a developer', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $response = $this->actingAs($developer)->get(route('dashboard', ['page' => 'developer-access']));

    $response->assertOk();
    expect($response->viewData('adminPage'))->toBe('developer-access');
    $response->assertSee(route('admin.system-users.update', $staff), false);
});

test('shows the restricted notice to a non super admin admin', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($subAdmin)->get(route('dashboard', ['page' => 'developer-access']));

    $response->assertOk();
    $response->assertSee('শুধুমাত্র সুপার অ্যাডমিন অ্যাকাউন্ট ডেভেলপার অ্যাক্সেস পরিচালনা করতে পারে।');
    $response->assertDontSee(route('admin.system-users.update', $subAdmin), false);
});

test('lets a super admin change the role of a system user', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $response = $this->actingAs($developer)->put(route('admin.system-users.update', $staff), [
        'role' => User::ROLE_ADMIN,
    ]);

    $response->assertRedirect(route('dashboard', ['page' => 'developer-access']));
    $response->assertSessionHas('toast', 'অ্যাক্সেস লেভেল সফলভাবে আপডেট হয়েছে!');

    expect($staff->fresh()->role)->toBe(User::ROLE_ADMIN);
    expect(AdminActivity::where('action', 'role_changed')->where('subject', $staff->name)->exists())->toBeTrue();
});

test('forbids an admin from changing access levels', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $this->actingAs($subAdmin)->put(route('admin.system-users.update', $staff), [
        'role' => User::ROLE_ADMIN,
    ])->assertForbidden();

    expect($staff->fresh()->role)->toBe(User::ROLE_CUSTOMER_CARE);
});

test('forbids a super admin from changing their own access level', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $this->actingAs($developer)->put(route('admin.system-users.update', $developer), [
        'role' => User::ROLE_CUSTOMER_CARE,
    ])->assertForbidden();

    expect($developer->fresh()->role)->toBe(User::ROLE_SUPER_ADMIN);
});

test('forbids client users from changing access levels', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $this->actingAs($client)->put(route('admin.system-users.update', $staff), [
        'role' => User::ROLE_DEVELOPER,
    ])->assertForbidden();
});

test('rejects an unknown role when changing access levels', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);

    $this->actingAs($developer)->put(route('admin.system-users.update', $staff), [
        'role' => 'superuser',
    ])->assertSessionHasErrors('role');

    expect($staff->fresh()->role)->toBe(User::ROLE_CUSTOMER_CARE);
});

test('shows the saved permission matrix on the roles page', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    Setting::putBoolean(User::permissionKey(User::ROLE_ADMIN, 'create_staff'), false);

    $response = $this->actingAs($developer)->get(route('dashboard', ['page' => 'roles-permission']));

    $response->assertOk();
    expect($response->viewData('adminPage'))->toBe('roles-permission');
    expect($response->viewData('permissionMatrix')[User::ROLE_ADMIN]['create_staff'])->toBeFalse();
    expect($response->viewData('permissionMatrix')[User::ROLE_DEVELOPER]['create_staff'])->toBeTrue();
});

test('lets a super admin save role permissions', function () {
    $developer = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $response = $this->actingAs($developer)->post(route('admin.roles-permission.update'), [
        'permissions' => [
            User::ROLE_DEVELOPER => ['create_staff', 'remove_staff'],
            User::ROLE_ADMIN => ['create_staff'],
        ],
    ]);

    $response->assertRedirect(route('dashboard', ['page' => 'roles-permission']));
    $response->assertSessionHas('toast', 'পারমিশন সফলভাবে আপডেট হয়েছে!');

    expect(Setting::boolean(User::permissionKey(User::ROLE_ADMIN, 'create_staff'), false))->toBeTrue();
    expect(Setting::boolean(User::permissionKey(User::ROLE_ADMIN, 'remove_staff'), true))->toBeFalse();
    expect(Setting::boolean(User::permissionKey(User::ROLE_CUSTOMER_CARE, 'create_staff'), true))->toBeFalse();
    expect(AdminActivity::where('action', 'permission_updated')->exists())->toBeTrue();
});

test('forbids non super admins from saving role permissions', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($subAdmin)->post(route('admin.roles-permission.update'), [
        'permissions' => [
            User::ROLE_ADMIN => ['create_staff', 'remove_staff'],
        ],
    ])->assertForbidden();

    expect(Setting::boolean(User::permissionKey(User::ROLE_DEVELOPER, 'create_staff'), true))->toBeTrue();
});

test('applies the create staff permission when creating system users', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Setting::putBoolean(User::permissionKey(User::ROLE_ADMIN, 'create_staff'), false);

    $this->actingAs($subAdmin)->post(route('admin.system-users.store'), [
        'name' => 'Blocked Staff',
        'email' => 'blocked@example.com',
        'password' => 'secret1234',
    ])->assertForbidden();

    expect(User::where('email', 'blocked@example.com')->exists())->toBeFalse();
});

test('applies the remove staff permission when removing system users', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $staff = User::factory()->create(['role' => User::ROLE_CUSTOMER_CARE]);
    Setting::putBoolean(User::permissionKey(User::ROLE_ADMIN, 'remove_staff'), false);

    $this->actingAs($subAdmin)
        ->delete(route('admin.system-users.destroy', $staff))
        ->assertForbidden();

    expect(User::find($staff->id))->not->toBeNull();
});

test('shows the no permission notice when create access is disabled', function () {
    $subAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Setting::putBoolean(User::permissionKey(User::ROLE_ADMIN, 'create_staff'), false);

    $response = $this->actingAs($subAdmin)->get(route('dashboard', ['page' => 'system-users']));

    $response->assertOk();
    $response->assertSee('আপনার নতুন ইউজার তৈরি করার অনুমতি নেই।');
    $response->assertDontSee(route('admin.system-users.store'));
});
