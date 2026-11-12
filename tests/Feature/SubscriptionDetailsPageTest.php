<?php

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTrial;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function detailsPagePlan(string $product, string $cycle): SubscriptionPlan
{
    return SubscriptionPlan::query()
        ->where('product', $product)
        ->where('billing_cycle', $cycle)
        ->firstOrFail();
}

function detailsPageSubscribe(User $user, SubscriptionPlan $plan, string $status, string $startsAt, string $endsAt, ?SubscriptionTrial $trial = null): UserSubscription
{
    return UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => $plan->billing_cycle,
        'subscription_trial_id' => $trial?->getKey(),
        'status' => $status,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
}

function detailsPageTrial(User $user, string $product, string $startsAt, string $endsAt): SubscriptionTrial
{
    return SubscriptionTrial::query()->create([
        'user_id' => $user->getKey(),
        'product' => $product,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
}

test('a user without a subscription sees the compact summary, trial offer and pricing', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSee(__('subscription.details.title', ['product' => __('subscription.products.student')]));
    $response->assertSeeText(__('subscription.status.inactive'));
    $response->assertSeeText(__('subscription.trial.eligible'));
    $response->assertSeeText(__('subscription.trial.button'));
    $response->assertSeeText(__('subscription.plans.heading'));
    $response->assertSeeText(__('subscription.plans.included'));
    $response->assertSee('value="'.detailsPagePlan('student', 'monthly')->slug.'"', false);
    $response->assertSee('value="'.detailsPagePlan('student', 'yearly')->slug.'"', false);
    $response->assertDontSeeText(__('subscription.open'));
});

test('an active trial shows the trial badge, message and open action without a duplicate trial button', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $startsAt = now()->subDay();
    $endsAt = now()->addDays(29);

    $trial = detailsPageTrial($user, 'student', $startsAt->toDateTimeString(), $endsAt->toDateTimeString());
    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'trial'),
        UserSubscription::STATUS_ACTIVE,
        $startsAt->toDateTimeString(),
        $endsAt->toDateTimeString(),
        $trial,
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.status.trial'));
    $response->assertSeeText(__('subscription.trial.started'));
    $response->assertSeeText(__('subscription.open'));
    $response->assertSee($endsAt->format('d M Y'));
    $response->assertDontSeeText(__('subscription.trial.button'));
});

test('an active paid monthly subscription shows the plan, remaining days and management action', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $startsAt = now()->subDays(5);
    $endsAt = now()->addDays(25);

    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'monthly'),
        UserSubscription::STATUS_ACTIVE,
        $startsAt->toDateTimeString(),
        $endsAt->toDateTimeString(),
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.status.active'));
    $response->assertSee('Monthly');
    $response->assertSeeText(__('subscription.open'));
    $response->assertSeeText(__('subscription.trial.unavailable_active'));
    $response->assertSee($endsAt->format('d M Y'));
    $response->assertSee(localize_number(25), false);
    $response->assertDontSeeText(__('subscription.trial.button'));
});

test('an active paid yearly subscription shows the yearly plan and keeps both checkout options', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'yearly'),
        UserSubscription::STATUS_ACTIVE,
        now()->subDays(10)->toDateTimeString(),
        now()->addDays(355)->toDateTimeString(),
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.status.active'));
    $response->assertSee('Yearly');
    $response->assertSee('value="'.detailsPagePlan('student', 'monthly')->slug.'"', false);
    $response->assertSee('value="'.detailsPagePlan('student', 'yearly')->slug.'"', false);
    $response->assertSeeText(__('subscription.plans.yearly'));
});

test('an expired subscription shows the expired state and guidance while keeping plans available', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'monthly'),
        UserSubscription::STATUS_ACTIVE,
        now()->subDays(40)->toDateTimeString(),
        now()->subDay()->toDateTimeString(),
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.status.expired'));
    $response->assertSeeText(__('subscription.current.expired_subscription'));
    $response->assertSee('value="'.detailsPagePlan('student', 'monthly')->slug.'"', false);
    $response->assertDontSeeText(__('subscription.open'));
});

test('a cancelled subscription shows the cancelled state and renewal guidance', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'monthly'),
        UserSubscription::STATUS_CANCELLED,
        now()->subDays(5)->toDateTimeString(),
        now()->addDays(25)->toDateTimeString(),
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.status.cancelled'));
    $response->assertSeeText(__('subscription.current.cancelled_subscription'));
    $response->assertSee('value="'.detailsPagePlan('student', 'yearly')->slug.'"', false);
    $response->assertDontSeeText(__('subscription.open'));
});

test('a used trial is indicated without offering another trial', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    detailsPageTrial(
        $user,
        'student',
        now()->subDays(40)->toDateTimeString(),
        now()->subDays(10)->toDateTimeString(),
    );

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.trial.used'));
    $response->assertDontSeeText(__('subscription.trial.button'));
});

test('each product keeps its own summary and checkout options', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $startsAt = now()->subDay();
    $endsAt = now()->addDays(29);

    $trial = detailsPageTrial($user, 'student', $startsAt->toDateTimeString(), $endsAt->toDateTimeString());
    detailsPageSubscribe(
        $user,
        detailsPagePlan('student', 'trial'),
        UserSubscription::STATUS_ACTIVE,
        $startsAt->toDateTimeString(),
        $endsAt->toDateTimeString(),
        $trial,
    );

    detailsPageSubscribe(
        $user,
        detailsPagePlan('family', 'monthly'),
        UserSubscription::STATUS_ACTIVE,
        now()->subDays(2)->toDateTimeString(),
        now()->addDays(28)->toDateTimeString(),
    );

    $familyResponse = $this->actingAs($user)
        ->get(route('subscriptions.show', 'family'))
        ->assertOk();

    $familyResponse->assertSeeText(__('subscription.status.active'));
    $familyResponse->assertSee('value="'.detailsPagePlan('family', 'monthly')->slug.'"', false);
    $familyResponse->assertDontSee('value="'.detailsPagePlan('shop', 'monthly')->slug.'"', false);
    $familyResponse->assertDontSeeText(__('subscription.trial.button'));

    $shopResponse = $this->actingAs($user)
        ->get(route('subscriptions.show', 'shop'))
        ->assertOk();

    $shopResponse->assertSeeText(__('subscription.status.inactive'));
    $shopResponse->assertSeeText(__('subscription.trial.button'));
    $shopResponse->assertSee(route('subscriptions.trial', 'shop'), false);
    $shopResponse->assertDontSee('value="'.detailsPagePlan('family', 'yearly')->slug.'"', false);
});

test('verified payments appear in the compact history table', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = detailsPagePlan('student', 'monthly');

    detailsPageSubscribe(
        $user,
        $plan,
        UserSubscription::STATUS_ACTIVE,
        now()->subDay()->toDateTimeString(),
        now()->addDays(29)->toDateTimeString(),
    );

    $payment = Payment::factory()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'product' => $plan->product,
        'billing_cycle' => $plan->billing_cycle,
        'amount' => $plan->price,
        'status' => Payment::STATUS_SUCCESSFUL,
        'payment_method' => 'bkash',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk();

    $response->assertSeeText(__('subscription.payment.history'));
    $response->assertSee($payment->transaction_id);
    $response->assertDontSeeText(__('subscription.payment.no_history'));
});
