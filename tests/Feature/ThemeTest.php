<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\MessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function themeTestPlan(): SubscriptionPlan
{
    return SubscriptionPlan::query()->firstOrCreate(['slug' => 'theme-test-student'], [
        'name' => 'Theme Test Student',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => 50,
        'currency' => 'BDT',
        'duration_days' => 30,
        'is_active' => true,
    ]);
}

function themeTestStudent(): User
{
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => themeTestPlan()->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    app(MessService::class)->create($user, [
        'name' => 'Theme Test Mess',
        'address' => 'Dhaka',
        'start_date' => now()->startOfMonth()->toDateString(),
        'billing_month' => now()->format('Y-m'),
        'monthly_budget' => 10000,
    ]);

    return $user;
}

test('the meal management layout initializes the persisted theme before the page renders', function () {
    $user = themeTestStudent();

    $response = $this->actingAs($user)->get(route('student.dashboard'))->assertOk();

    $html = $response->getContent();
    $initPosition = strpos($html, 'window.hisabTheme');
    $tailwindPosition = strpos($html, 'cdn.tailwindcss.com');

    expect($initPosition)->not->toBeFalse()
        ->and($tailwindPosition)->not->toBeFalse()
        ->and($initPosition)->toBeLessThan($tailwindPosition);

    $response->assertSee('theme-dark')
        ->assertSee('prefers-color-scheme: dark')
        ->assertSee('hisab-theme-changed')
        ->assertSee('toggleDark()')
        ->assertSee('html:not(.dark) .text-white')
        ->assertSee('.student-sidebar')
        ->assertSee('.student-topbar')
        ->assertDontSee(':class="dark ? \'dark\' : \'\'"', false);
});

test('the shared client layout uses the same persisted theme system', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();

    $response->assertSee('window.hisabTheme')
        ->assertSee('theme-dark')
        ->assertSee('html:not(.dark) .text-white')
        ->assertDontSee(':class="dark ? \'dark\' : \'\'"', false);
});

test('the dashboard charts update their colors when the theme changes', function () {
    $user = themeTestStudent();

    $response = $this->actingAs($user)->get(route('student.dashboard'))->assertOk();

    $response->assertSee('hisab-theme-changed')
        ->assertSee('tooltipBackground')
        ->assertSee('doughnutBorder')
        ->assertSee("breakdownChart.update('none')", false)
        ->assertSee("incomeChart.update('none')", false);
});
