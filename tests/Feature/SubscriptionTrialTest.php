<?php

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTrial;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the first activation grants exactly thirty days and records permanent trial history', function () {
    $this->travelTo(now()->setDate(2026, 1, 1)->setTime(9, 0));

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)
        ->post(route('subscriptions.trial', 'student'))
        ->assertRedirect(route('subscriptions.show', 'student'));

    $trial = SubscriptionTrial::query()->where('user_id', $user->getKey())->where('product', 'student')->firstOrFail();
    $subscription = UserSubscription::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($trial->starts_at->equalTo(now()))->toBeTrue()
        ->and($trial->ends_at->getTimestamp() - $trial->starts_at->getTimestamp())->toBe(30 * 86400)
        ->and($subscription->subscription_trial_id)->toBe($trial->getKey())
        ->and($subscription->ends_at->equalTo($trial->ends_at))->toBeTrue()
        ->and($user->fresh()->isEligibleForTrial('student'))->toBeFalse();
});

test('a second trial activation is blocked and never creates a duplicate record', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();
    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))
        ->assertRedirect(route('subscriptions.show', 'student'));

    expect(SubscriptionTrial::query()->count())->toBe(1)
        ->and(UserSubscription::query()->whereNotNull('subscription_trial_id')->count())->toBe(1);
});

test('retired products are no longer valid trial targets', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)->post(route('subscriptions.trial', 'family'))->assertNotFound();
    $this->actingAs($user)->post(route('subscriptions.trial', 'shop'))->assertNotFound();

    expect(SubscriptionTrial::query()->where('user_id', $user->getKey())->count())->toBe(0);
});

test('trial access ends after exactly thirty days while eligibility history is preserved', function () {
    $start = now()->setDate(2026, 2, 1)->setTime(12, 0);
    $this->travelTo($start);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();

    $this->travelTo($start->copy()->addDays(30)->subSecond());
    expect($user->fresh()->hasActiveSubscription('student'))->toBeTrue();

    $this->travelTo($start->copy()->addDays(30)->addSecond());
    expect($user->fresh()->hasActiveSubscription('student'))->toBeFalse();
    expect(SubscriptionTrial::query()->where('user_id', $user->getKey())->exists())->toBeTrue();
    expect($user->fresh()->isEligibleForTrial('student'))->toBeFalse();

    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();
    expect(SubscriptionTrial::query()->where('user_id', $user->getKey())->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));
});

test('trial eligibility is never reset by expiration or renewal', function () {
    $start = now();
    $this->travelTo($start);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();

    $plan = SubscriptionPlan::query()->create([
        'slug' => 'renewal-student-monthly',
        'name' => 'Renewal Student Monthly',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => 50,
        'currency' => 'BDT',
        'duration_days' => 30,
        'is_active' => true,
    ]);

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => $start,
        'ends_at' => $start->copy()->addDays(30),
    ]);

    expect($user->fresh()->isEligibleForTrial('student'))->toBeFalse()
        ->and(SubscriptionTrial::query()->where('user_id', $user->getKey())->count())->toBe(1);

    $this->travelTo($start->copy()->addDays(45));

    expect($user->fresh()->isEligibleForTrial('student'))->toBeFalse()
        ->and(SubscriptionTrial::query()->where('user_id', $user->getKey())->count())->toBe(1);
});

test('subscription page shows the trial option and paid plans only while the trial is available', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $monthly = SubscriptionPlan::query()->create([
        'slug' => 'ui-student-monthly',
        'name' => 'Meal Management — Monthly',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => 50,
        'currency' => 'BDT',
        'duration_days' => 30,
        'is_active' => true,
    ]);
    $yearly = SubscriptionPlan::query()->create([
        'slug' => 'ui-student-yearly',
        'name' => 'Meal Management — Yearly',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_YEARLY,
        'price' => 500,
        'currency' => 'BDT',
        'duration_days' => 365,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertSee(__('subscription.products.student'));

    $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk()
        ->assertSee(__('subscription.trial.button'))
        ->assertSee('value="'.$monthly->slug.'"', false)
        ->assertSee('value="'.$yearly->slug.'"', false);

    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();

    $this->actingAs($user)
        ->get(route('subscriptions.show', 'student'))
        ->assertOk()
        ->assertDontSee(__('subscription.trial.button'))
        ->assertSeeText(__('subscription.trial.started'))
        ->assertSee('value="'.$monthly->slug.'"', false)
        ->assertSee('value="'.$yearly->slug.'"', false);
});

test('a user with active paid access cannot start the same product trial', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = SubscriptionPlan::query()->create([
        'slug' => 'active-student-monthly',
        'name' => 'Active Student Monthly',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => 50,
        'currency' => 'BDT',
        'duration_days' => 30,
        'is_active' => true,
    ]);

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();

    expect(SubscriptionTrial::query()->where('user_id', $user->getKey())->exists())->toBeFalse();
});
