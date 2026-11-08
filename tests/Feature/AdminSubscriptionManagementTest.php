<?php

use App\Models\AdminActivity;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTrial;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SslCommerzGateway;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminSubscriptionPlan(string $product = 'student', string $cycle = 'monthly', float $price = 50): SubscriptionPlan
{
    return SubscriptionPlan::query()->create([
        'slug' => 'admin-'.$product.'-'.$cycle.'-'.uniqid(),
        'name' => ucfirst($product).' '.ucfirst($cycle).' Plan',
        'product' => $product,
        'billing_cycle' => $cycle,
        'price' => $price,
        'currency' => 'BDT',
        'duration_days' => $cycle === SubscriptionPlan::BILLING_CYCLE_YEARLY ? 365 : 30,
        'is_active' => true,
    ]);
}

function adminSubscriptionFor(User $user, SubscriptionPlan $plan, string $status = UserSubscription::STATUS_ACTIVE, ?string $endsAt = null): UserSubscription
{
    return UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => $plan->billing_cycle,
        'status' => $status,
        'starts_at' => now()->subDay(),
        'ends_at' => $endsAt ? Carbon::parse($endsAt) : now()->addDays(30),
    ]);
}

function adminSuccessfulPayment(User $user, SubscriptionPlan $plan): Payment
{
    return Payment::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'transaction_id' => 'HK-'.uniqid(),
        'gateway' => Payment::GATEWAY_SSLCOMMERZ,
        'gateway_transaction_id' => 'BANK-'.uniqid(),
        'product' => $plan->product,
        'billing_cycle' => $plan->billing_cycle,
        'amount' => $plan->price,
        'currency' => 'BDT',
        'status' => Payment::STATUS_SUCCESSFUL,
        'payment_method' => 'bKash',
        'paid_at' => now(),
        'ipn_verified_at' => now(),
    ]);
}

test('the admin panel shows subscriptions, trials, payments and revenue', function () {
    $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT, 'email' => 'admin-view-client@example.com']);
    $plan = adminSubscriptionPlan();
    $subscription = adminSubscriptionFor($client, $plan);
    $payment = adminSuccessfulPayment($client, $plan);
    SubscriptionTrial::query()->create([
        'user_id' => $client->getKey(),
        'product' => 'student',
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('dashboard', ['page' => 'admin-subscriptions']))
        ->assertOk();

    $response->assertSee('admin-view-client@example.com')
        ->assertSee($plan->name)
        ->assertSee($payment->transaction_id);

    $this->actingAs($admin)
        ->get(route('dashboard', ['page' => 'admin-pricing']))
        ->assertOk()
        ->assertSee($plan->slug);
});

test('only a super admin can suspend and reactivate subscriptions with audit logs', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $regularAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = adminSubscriptionPlan();
    $subscription = adminSubscriptionFor($client, $plan);

    $this->actingAs($regularAdmin)
        ->get(route('dashboard', ['page' => 'admin-subscriptions']))
        ->assertOk();

    $this->actingAs($regularAdmin)
        ->post(route('admin.subscriptions.suspend', $subscription))
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->post(route('admin.subscriptions.suspend', $subscription), ['reason' => 'Payment review'])
        ->assertRedirect(route('dashboard', ['page' => 'admin-subscriptions']));

    expect($subscription->fresh()->status)->toBe(UserSubscription::STATUS_SUSPENDED)
        ->and($client->fresh()->hasActiveSubscription('student'))->toBeFalse()
        ->and(AdminActivity::query()->where('action', 'subscription_suspended')->exists())->toBeTrue();

    $this->actingAs($superAdmin)
        ->post(route('admin.subscriptions.reactivate', $subscription))
        ->assertRedirect(route('dashboard', ['page' => 'admin-subscriptions']));

    expect($subscription->fresh()->status)->toBe(UserSubscription::STATUS_ACTIVE)
        ->and($client->fresh()->hasActiveSubscription('student'))->toBeTrue()
        ->and(AdminActivity::query()->where('action', 'subscription_reactivated')->exists())->toBeTrue();
});

test('a super admin can update product pricing', function () {
    $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $plan = adminSubscriptionPlan('student', 'monthly', 50);

    $this->actingAs($admin)
        ->put(route('admin.pricing.update', $plan), [
            'name' => 'Meal Management — Monthly Plus',
            'price' => 75,
            'duration_days' => 30,
            'is_active' => 1,
        ])
        ->assertRedirect(route('dashboard', ['page' => 'admin-pricing']));

    $plan->refresh();

    expect($plan->name)->toBe('Meal Management — Monthly Plus')
        ->and((float) $plan->price)->toBe(75.0)
        ->and(AdminActivity::query()->where('action', 'subscription_plan_updated')->exists())->toBeTrue();
});

test('a regular admin cannot change pricing or gateway credentials', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $plan = adminSubscriptionPlan();

    $this->actingAs($admin)
        ->put(route('admin.pricing.update', $plan), [
            'name' => 'Hacked',
            'price' => 1,
            'duration_days' => 1,
            'is_active' => 1,
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.payment-gateway.update'), [
            'store_id' => 'hacked-store',
            'store_password' => 'hacked-password',
            'sandbox' => 0,
        ])
        ->assertForbidden();

    expect($plan->fresh()->name)->not->toBe('Hacked')
        ->and(Setting::value(SslCommerzGateway::STORE_ID_KEY))->toBeNull();
});

test('gateway credentials are encrypted at rest and never exposed in plain text', function () {
    $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

    $this->actingAs($admin)
        ->put(route('admin.payment-gateway.update'), [
            'store_id' => 'hisab-store',
            'store_password' => 'super-secret-password',
            'sandbox' => 0,
        ])
        ->assertRedirect(route('dashboard', ['page' => 'admin-pricing']));

    $rawPassword = Setting::value(SslCommerzGateway::STORE_PASSWORD_KEY);

    expect(Setting::value(SslCommerzGateway::STORE_ID_KEY))->toBe('hisab-store')
        ->and($rawPassword)->not->toBe('super-secret-password')
        ->and(Setting::secret(SslCommerzGateway::STORE_PASSWORD_KEY))->toBe('super-secret-password')
        ->and(Setting::boolean(SslCommerzGateway::SANDBOX_KEY))->toBeFalse()
        ->and(AdminActivity::query()->where('action', 'payment_gateway_updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('dashboard', ['page' => 'admin-pricing']))
        ->assertOk()
        ->assertSee('hisab-store')
        ->assertDontSee('super-secret-password');
});
