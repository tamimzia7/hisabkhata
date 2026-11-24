<?php

use App\Models\Mess;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\MessService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clientNavPlan(): SubscriptionPlan
{
    return SubscriptionPlan::query()->create([
        'slug' => 'client-nav-student-monthly',
        'name' => 'Client Navigation Student Monthly',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'price' => 100,
        'currency' => SubscriptionPlan::CURRENCY_BDT,
        'duration_days' => 30,
        'is_active' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function clientNavSubscribe(User $user, array $attributes = []): UserSubscription
{
    return UserSubscription::query()->create(array_merge([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => clientNavPlan()->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ], $attributes));
}

function clientNavMess(User $user): Mess
{
    return app(MessService::class)->create($user, [
        'name' => 'Navigation Mess',
        'address' => 'Dhanmondi, Dhaka',
        'start_date' => now()->startOfMonth()->toDateString(),
        'billing_month' => now()->format('Y-m'),
        'monthly_budget' => 20000,
    ]);
}

function clientNavSidebarDocument(string $html): DOMXPath
{
    $dom = new DOMDocument;

    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function clientNavSidebarLinkCount(string $html, string $href): int
{
    return clientNavSidebarDocument($html)->query('//aside//a[@href="'.$href.'"]')->length;
}

function clientNavSidebarText(string $html): string
{
    $node = clientNavSidebarDocument($html)->query('//aside')->item(0);

    return (string) $node?->textContent;
}

test('a newly registered client sees only onboarding and global navigation', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $response = $this->actingAs($client)->get(route('dashboard'))->assertOk();
    $html = $response->getContent();
    $sidebarText = clientNavSidebarText($html);

    $response
        ->assertSee(__('site.dashboard.dashboard'))
        ->assertSee(__('subscription.title'))
        ->assertSee(__('student.nav.module_link'))
        ->assertSee(__('tickets.support'))
        ->assertSee(__('notifications.sidebar_label'))
        ->assertSee(__('site.dashboard.logout'));

    expect($html)
        ->toContain(route('subscriptions.show', SubscriptionPlan::PRODUCT_STUDENT))
        ->not->toContain(route('student.dashboard'))
        ->and($sidebarText)
        ->not->toContain(__('student.nav.members'))
        ->not->toContain(__('student.nav.meals'))
        ->not->toContain(__('site.dashboard.my_profile'));
});

test('an active free trial reveals the product workspace navigation', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    app(SubscriptionService::class)->startTrial($client, SubscriptionPlan::PRODUCT_STUDENT);
    $mess = clientNavMess($client);

    $response = $this->actingAs($client)->get(route('dashboard'))->assertOk();

    $response
        ->assertSee(__('subscription.products.student'))
        ->assertSee(__('student.nav.members'))
        ->assertSee(__('student.nav.meals'))
        ->assertSee(__('student.nav.bazar'))
        ->assertSee(__('student.nav.reports'))
        ->assertSee(__('site.dashboard.my_profile'))
        ->assertSee(route('student.dashboard'), false)
        ->assertSee(route('student.mess.members.index', $mess), false)
        ->assertSee(route('student.profile.edit'), false)
        ->assertSee('aria-current="page"', false);
});

test('an active paid subscription reveals the product workspace navigation', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    clientNavSubscribe($client);
    $mess = clientNavMess($client);

    $response = $this->actingAs($client)->get(route('dashboard'))->assertOk();

    $response
        ->assertSee(__('student.nav.accounts'))
        ->assertSee(__('student.nav.transactions'))
        ->assertSee(__('student.nav.settlements'))
        ->assertSee(route('student.mess.accounts.index', $mess), false)
        ->assertSee(route('student.mess.transactions.index', $mess), false)
        ->assertSee(route('subscriptions.index'), false);
});

test('non-active subscription states hide the product workspace navigation', function (string $status, string $endsAt, bool $suspended) {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    clientNavSubscribe($client, [
        'status' => $status,
        'starts_at' => now()->subMonth(),
        'ends_at' => $endsAt === 'past' ? now()->subDay() : now()->addDays(30),
        'suspended_at' => $suspended ? now() : null,
    ]);

    $response = $this->actingAs($client)->get(route('dashboard'))->assertOk();

    $response
        ->assertSee(__('subscription.onboarding.title'))
        ->assertSee(route('subscriptions.show', SubscriptionPlan::PRODUCT_STUDENT), false)
        ->assertDontSee(route('student.dashboard'), false)
        ->assertDontSee(__('student.nav.members'))
        ->assertDontSee(__('student.nav.accounts'));
})->with([
    'expired by date' => [UserSubscription::STATUS_ACTIVE, 'past', false],
    'expired status' => [UserSubscription::STATUS_EXPIRED, 'future', false],
    'cancelled' => [UserSubscription::STATUS_CANCELLED, 'future', false],
    'suspended' => [UserSubscription::STATUS_ACTIVE, 'future', true],
]);

test('an expired trial hides product navigation and direct product URLs remain gated', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $start = now()->subDays(31);

    $this->travelTo($start);
    app(SubscriptionService::class)->startTrial($client, SubscriptionPlan::PRODUCT_STUDENT);

    $this->travelTo($start->copy()->addDays(31));

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('subscription.onboarding.title'))
        ->assertDontSee(route('student.dashboard'), false);

    $this->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));
});

test('the student workspace keeps global account links without duplicating product features', function () {
    $client = User::factory()->create(['role' => User::ROLE_STUDENT]);
    clientNavSubscribe($client);
    $mess = clientNavMess($client);

    $response = $this->actingAs($client)->get(route('student.dashboard'))->assertOk();
    $html = $response->getContent();

    expect(clientNavSidebarLinkCount($html, route('student.mess.members.index', $mess)))->toBe(1)
        ->and(clientNavSidebarLinkCount($html, route('tickets.index')))->toBe(1)
        ->and(clientNavSidebarLinkCount($html, route('notifications.index')))->toBe(1);

    $response
        ->assertSee(__('student.nav.members'))
        ->assertSee(__('student.nav.profile'))
        ->assertSee(__('student.nav.logout'));
});
