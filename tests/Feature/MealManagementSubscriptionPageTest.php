<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mealSubscriptionPagePlan(string $cycle): SubscriptionPlan
{
    return SubscriptionPlan::query()
        ->where('product', SubscriptionPlan::PRODUCT_STUDENT)
        ->where('billing_cycle', $cycle)
        ->firstOrFail();
}

test('guests are redirected away from the meal management subscription page', function () {
    $this->get(route('subscriptions.meal-management'))
        ->assertRedirect(route('login'));
});

test('an eligible user sees the warning, trial offer and both checkout options', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($user)
        ->get(route('subscriptions.meal-management'))
        ->assertOk();

    $response->assertSeeText(__('subscription.landing.warning_title'))
        ->assertSeeText(__('subscription.landing.trial_start'))
        ->assertSeeText(__('subscription.landing.features_title'))
        ->assertSee('value="'.mealSubscriptionPagePlan('monthly')->slug.'"', false)
        ->assertSee('value="'.mealSubscriptionPagePlan('yearly')->slug.'"', false);
});

test('an active subscriber sees the dashboard action without the warning or trial offer', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = mealSubscriptionPagePlan('monthly');

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => $plan->billing_cycle,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(29),
    ]);

    $response = $this->actingAs($user)
        ->get(route('subscriptions.meal-management'))
        ->assertOk();

    $response->assertDontSeeText(__('subscription.landing.warning_title'))
        ->assertDontSeeText(__('subscription.landing.trial_start'))
        ->assertSeeText(__('subscription.open'));
});
