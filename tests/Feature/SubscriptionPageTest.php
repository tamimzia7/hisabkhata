<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function subscriptionPagePlan(string $product, string $cycle): SubscriptionPlan
{
    return SubscriptionPlan::query()
        ->where('product', $product)
        ->where('billing_cycle', $cycle)
        ->firstOrFail();
}

function subscriptionPageSubscribe(User $user, SubscriptionPlan $plan, string $status, ?string $endsAt): UserSubscription
{
    return UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => $plan->billing_cycle,
        'status' => $status,
        'starts_at' => now()->subDays(2),
        'ends_at' => $endsAt,
    ]);
}

test('the subscription overview counts only real active records', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    subscriptionPageSubscribe($user, subscriptionPagePlan('student', 'monthly'), UserSubscription::STATUS_ACTIVE, now()->addDays(30)->toDateTimeString());

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertSee(__('subscription.page.title'))
        ->assertSee(__('subscription.choose_product'))
        ->assertViewHas('overview', fn (array $overview): bool => $overview['active'] === 1
            && $overview['trialEligible'] === 0
            && $overview['upcoming'] === 0);
});

test('upcoming expirations include only active subscriptions inside the window', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    subscriptionPageSubscribe($user, subscriptionPagePlan('student', 'monthly'), UserSubscription::STATUS_ACTIVE, now()->addDays(3)->toDateTimeString());

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertViewHas('overview', fn (array $overview): bool => $overview['active'] === 1
            && $overview['upcoming'] === 1);
});

test('product cards expose the correct call to action for each lifecycle state', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertViewHas('products', function (array $products): bool {
            $items = collect($products)->keyBy('product');

            return $items['student']['status'] === 'inactive'
                && $items['student']['cta']['method'] === 'post'
                && $items['student']['cta']['label'] === __('subscription.cta.start_trial')
                && $items['student']['secondaryCta']['label'] === __('subscription.cta.explore');
        });
});

test('billing data reflects the configured monthly and yearly prices', function () {
    SubscriptionPlan::query()->where('slug', 'student-monthly')->update(['price' => 60]);
    SubscriptionPlan::query()->where('slug', 'student-yearly')->update(['price' => 600]);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();

    $student = collect($response->viewData('products'))->firstWhere('product', 'student');

    expect((float) $student['monthlyPrice'])->toBe(60.0)
        ->and((float) $student['yearlyPrice'])->toBe(600.0)
        ->and($student['annualSavings'])->toBe(120.0);
});

test('starting a trial keeps the product payload consistent', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)->post(route('subscriptions.trial', 'student'))->assertRedirect();

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertViewHas('products', function (array $products): bool {
            $items = collect($products)->keyBy('product');

            return $items['student']['status'] === 'trial'
                && $items['student']['cta']['label'] === __('subscription.cta.manage');
        });
});

test('an active paid subscription offers management instead of a duplicate trial', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    subscriptionPageSubscribe($user, subscriptionPagePlan('student', 'monthly'), UserSubscription::STATUS_ACTIVE, now()->addDays(30)->toDateTimeString());

    $this->actingAs($user)
        ->get(route('subscriptions.index'))
        ->assertOk()
        ->assertViewHas('products', function (array $products): bool {
            $student = collect($products)->firstWhere('product', 'student');

            return $student['status'] === 'active'
                && $student['trialEligible'] === false
                && $student['cta']['label'] === __('subscription.cta.manage');
        });
});
