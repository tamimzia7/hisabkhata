<?php

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function onboardingTestPlan(string $product, float $price = 75): SubscriptionPlan
{
    return SubscriptionPlan::query()->create([
        'slug' => 'onboarding-gate-'.$product.'-monthly',
        'name' => 'Onboarding '.ucfirst($product),
        'product' => $product,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => $price,
        'currency' => SubscriptionPlan::CURRENCY_BDT,
        'duration_days' => 30,
        'is_active' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function onboardingTestSubscribe(User $user, SubscriptionPlan $plan, array $attributes = []): UserSubscription
{
    return UserSubscription::query()->create(array_merge([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ], $attributes));
}

test('a newly registered client sees subscription onboarding instead of the product dashboard', function () {
    onboardingTestPlan(SubscriptionPlan::PRODUCT_STUDENT, 75);

    $this->post('/register', [
        'name' => 'Onboarding Client',
        'email' => 'onboarding@example.com',
        'password' => 'secret1234',
        'role' => User::ROLE_STUDENT,
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'onboarding@example.com')->firstOrFail();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('onboarding', true)
        ->assertViewHas('recommendedProduct', User::ROLE_STUDENT)
        ->assertSee(__('subscription.onboarding.title'))
        ->assertSee(__('subscription.onboarding.notice'))
        ->assertSee(__('subscription.onboarding.recommended'))
        ->assertSee(__('subscription.choose_product'))
        ->assertSee(__('subscription.products.student'))
        ->assertDontSee(__('site.dashboard.heading_student'));
});

test('starting a trial from onboarding unlocks the dashboard', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('subscription.onboarding.title'));

    $this->post(route('subscriptions.trial', SubscriptionPlan::PRODUCT_STUDENT))
        ->assertRedirect(route('subscriptions.show', SubscriptionPlan::PRODUCT_STUDENT));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('site.dashboard.heading_student'))
        ->assertDontSee(__('subscription.onboarding.title'));
});

test('an active paid subscription unlocks the dashboard', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = onboardingTestPlan(SubscriptionPlan::PRODUCT_STUDENT);
    onboardingTestSubscribe($user, $plan);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('site.dashboard.heading_student'))
        ->assertDontSee(__('subscription.onboarding.title'));
});

test('non-active subscriptions keep the onboarding gate closed', function (string $status, string $endsAt, bool $suspended) {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = onboardingTestPlan(SubscriptionPlan::PRODUCT_STUDENT);

    onboardingTestSubscribe($user, $plan, [
        'status' => $status,
        'starts_at' => now()->subMonth(),
        'ends_at' => $endsAt === 'past' ? now()->subDay() : now()->addDays(30),
        'suspended_at' => $suspended ? now() : null,
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('subscription.onboarding.title'))
        ->assertDontSee(__('site.dashboard.heading_student'));
})->with([
    'expired by date' => [UserSubscription::STATUS_ACTIVE, 'past', false],
    'expired status' => [UserSubscription::STATUS_EXPIRED, 'future', false],
    'cancelled' => [UserSubscription::STATUS_CANCELLED, 'future', false],
    'suspended' => [UserSubscription::STATUS_ACTIVE, 'future', true],
]);

test('a pending payment does not unlock the dashboard', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = onboardingTestPlan(SubscriptionPlan::PRODUCT_STUDENT);

    Payment::factory()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'status' => Payment::STATUS_PENDING,
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('subscription.onboarding.title'));
});

test('unsubscribed clients cannot bypass onboarding through direct product URLs', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('subscription.onboarding.title'));
});

test('admins bypass client onboarding without a subscription', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('site.dashboard.heading_admin'))
        ->assertDontSee(__('subscription.onboarding.title'));
});
