<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('renders the login page', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('লগইন');
    $response->assertSee(route('register'));
});

test('renders the registration page', function () {
    $response = $this->get('/register');

    $response->assertOk();
    $response->assertSee('রেজিস্ট্রেশন');
    $response->assertSee(route('login'));
});

test('registers a user, logs them in, and redirects to the dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'রাফি আহমেদ',
        'email' => 'rafi@example.com',
        'password' => 'secret1234',
        'mode' => 'student',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('toast', 'অ্যাকাউন্ট তৈরি হয়েছে! আপনার ড্যাশবোর্ডে স্বাগতম।');
    $this->assertAuthenticated();

    $user = User::where('email', 'rafi@example.com')->firstOrFail();

    expect($user->mode)->toBe('student');
    expect(Hash::check('secret1234', $user->password))->toBeTrue();
});

test('rejects registration when the email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'secret1234',
        'mode' => 'personal',
    ]);

    $response->assertSessionHasErrors(['email' => 'এই ইমেইল দিয়ে আগে থেকেই অ্যাকাউন্ট আছে।']);
    $this->assertGuest();
    expect(User::count())->toBe(1);
});

test('rejects registration with an unsupported mode', function () {
    $response = $this->post('/register', [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'secret1234',
        'mode' => 'admin',
    ]);

    $response->assertSessionHasErrors(['mode' => 'আপনার ধরন নির্বাচন করুন।']);
    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('rejects registration with a password shorter than eight characters', function () {
    $response = $this->post('/register', [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'password' => 'short',
        'mode' => 'business',
    ]);

    $response->assertSessionHasErrors(['password' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।']);
    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('logs in with valid credentials and shows the success toast', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('toast', 'সফলভাবে লগইন হয়েছে!');
    $this->assertAuthenticatedAs($user);
});

test('rejects login with an invalid password', function () {
    $user = User::factory()->create();

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email' => 'ইমেইল বা পাসওয়ার্ড মিলছে না।']);
    $this->assertGuest();
});

test('rate limits login after five failed attempts in a minute', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('redirects guests away from the dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

test('redirects authenticated users away from the login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('dashboard'));
});

test('shows the student dashboard to a student user', function () {
    $user = User::factory()->create(['mode' => 'student']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('মেস / শিক্ষার্থী ড্যাশবোর্ড');
    $response->assertSee($user->name);
    $response->assertDontSee('অ্যাডমিন প্যানেল');
});

test('shows the admin panel to an admin user', function () {
    $user = User::factory()->create(['mode' => 'admin']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('অ্যাডমিন প্যানেল');
    $response->assertDontSee('মেস / শিক্ষার্থী ড্যাশবোর্ড');
});

test('logs the user out and returns to the home page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete('/logout');

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('toast', 'লগআউট হয়েছে!');
    $this->assertGuest();
});
