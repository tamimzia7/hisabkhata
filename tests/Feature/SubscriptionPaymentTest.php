<?php

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function configurePaymentGateway(): void
{
    config([
        'services.sslcommerz.store_id' => 'test-store',
        'services.sslcommerz.store_password' => 'test-password',
        'services.sslcommerz.sandbox' => true,
    ]);
}

function paymentTestPlan(string $product = 'student', string $cycle = 'monthly', ?float $price = null): SubscriptionPlan
{
    $duration = $cycle === SubscriptionPlan::BILLING_CYCLE_YEARLY ? 365 : 30;

    return SubscriptionPlan::query()->firstOrCreate(
        ['slug' => 'test-'.$product.'-'.$cycle],
        [
            'name' => ucfirst($product).' '.ucfirst($cycle),
            'product' => $product,
            'billing_cycle' => $cycle,
            'price' => $price ?? ($cycle === SubscriptionPlan::BILLING_CYCLE_YEARLY ? 500 : 50),
            'currency' => SubscriptionPlan::CURRENCY_BDT,
            'duration_days' => $duration,
            'is_active' => true,
        ],
    );
}

function pendingPaymentFor(User $user, SubscriptionPlan $plan): Payment
{
    return app(PaymentService::class)->initiate($user, $plan);
}

function fakeGatewayCheckout(): void
{
    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response([
            'status' => 'SUCCESS',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/pay/test-session',
            'sessionkey' => 'session-key',
        ]),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeGatewayValidation(Payment $payment, array $overrides = []): void
{
    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response(array_merge([
            'status' => 'VALID',
            'tran_id' => $payment->transaction_id,
            'val_id' => 'VAL-'.$payment->getKey(),
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'bank_tran_id' => 'BANK-'.$payment->getKey(),
            'card_type' => 'bKash',
        ], $overrides)),
    ]);
}

test('checkout creates a pending payment and redirects to the SSLCommerz hosted page', function () {
    configurePaymentGateway();
    fakeGatewayCheckout();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();

    $this->actingAs($user)
        ->post(route('subscriptions.checkout', 'student'), ['plan' => $plan->slug])
        ->assertRedirect('https://sandbox.sslcommerz.com/pay/test-session');

    $payment = Payment::query()->firstOrFail();

    expect($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->amount)->toBe('50.00')
        ->and($payment->product)->toBe('student')
        ->and($payment->gateway_page_url)->toBe('https://sandbox.sslcommerz.com/pay/test-session')
        ->and($payment->initiated_at)->not->toBeNull()
        ->and($user->fresh()->hasActiveSubscription('student'))->toBeFalse();
});

test('a verified monthly payment activates exactly thirty days of access', function () {
    configurePaymentGateway();
    $start = now()->setDate(2026, 3, 1)->setTime(10, 0);
    $this->travelTo($start);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();
    $payment = pendingPaymentFor($user, $plan);
    fakeGatewayValidation($payment);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-'.$payment->getKey(),
    ])->assertRedirect(route('subscriptions.show', 'student'));

    $payment->refresh();
    $subscription = UserSubscription::query()->where('payment_id', $payment->getKey())->firstOrFail();

    expect($payment->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->gateway_transaction_id)->toBe('BANK-'.$payment->getKey())
        ->and($payment->payment_method)->toBe('bKash')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->ipn_verified_at)->not->toBeNull()
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->ends_at->getTimestamp() - $subscription->starts_at->getTimestamp())->toBe(30 * 86400);
});

test('a verified yearly payment activates exactly three hundred sixty five days of access', function () {
    configurePaymentGateway();
    $start = now()->setDate(2026, 4, 1)->setTime(10, 0);
    $this->travelTo($start);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan('student', SubscriptionPlan::BILLING_CYCLE_YEARLY);
    $payment = pendingPaymentFor($user, $plan);
    fakeGatewayValidation($payment);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-'.$payment->getKey(),
    ])->assertRedirect(route('subscriptions.show', 'student'));

    $subscription = UserSubscription::query()->where('payment_id', $payment->getKey())->firstOrFail();

    expect($subscription->ends_at->getTimestamp() - $subscription->starts_at->getTimestamp())->toBe(365 * 86400)
        ->and($subscription->billing_cycle)->toBe(SubscriptionPlan::BILLING_CYCLE_YEARLY);
});

test('an invalid gateway validation never activates a subscription', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $payment = pendingPaymentFor($user, paymentTestPlan());
    fakeGatewayValidation($payment, ['status' => 'INVALID']);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-INVALID',
    ])->assertRedirect(route('subscriptions.show', 'student'));

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and(UserSubscription::query()->where('payment_id', $payment->getKey())->exists())->toBeFalse()
        ->and($user->fresh()->hasActiveSubscription('student'))->toBeFalse();
});

test('a forged success request without a validation id cannot activate access', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $payment = pendingPaymentFor($user, paymentTestPlan());

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'status' => 'SUCCESS',
    ])->assertRedirect(route('subscriptions.show', 'student'));

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and(UserSubscription::query()->where('payment_id', $payment->getKey())->exists())->toBeFalse();
});

test('the paid amount and currency must match the plan before activation', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $payment = pendingPaymentFor($user, paymentTestPlan());
    fakeGatewayValidation($payment, ['amount' => '1.00']);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-'.$payment->getKey(),
    ])->assertRedirect(route('subscriptions.show', 'student'));

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and(UserSubscription::query()->where('payment_id', $payment->getKey())->exists())->toBeFalse();
});

test('failed and cancelled payments are recorded without access', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $failed = pendingPaymentFor($user, paymentTestPlan('student'));
    $cancelled = pendingPaymentFor($user, paymentTestPlan('student', SubscriptionPlan::BILLING_CYCLE_YEARLY));

    $this->post(route('payments.sslcommerz.fail'), [
        'tran_id' => $failed->transaction_id,
        'status' => 'FAILED',
    ])->assertRedirect(route('subscriptions.show', 'student'));

    $this->post(route('payments.sslcommerz.cancel'), [
        'tran_id' => $cancelled->transaction_id,
        'status' => 'CANCELLED',
    ])->assertRedirect(route('subscriptions.show', 'student'));

    expect($failed->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($cancelled->fresh()->status)->toBe(Payment::STATUS_CANCELLED)
        ->and(UserSubscription::query()->count())->toBe(0)
        ->and($user->fresh()->hasActiveSubscription('student'))->toBeFalse();
});

test('duplicate success callbacks and IPNs are idempotent', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $payment = pendingPaymentFor($user, paymentTestPlan());
    fakeGatewayValidation($payment);

    $payload = ['tran_id' => $payment->transaction_id, 'val_id' => 'VAL-'.$payment->getKey()];

    $this->post(route('payments.sslcommerz.success'), $payload)->assertRedirect();
    $this->post(route('payments.sslcommerz.success'), $payload)->assertRedirect();
    $this->post(route('payments.sslcommerz.ipn'), $payload)->assertOk();

    expect(Payment::query()->count())->toBe(1)
        ->and(UserSubscription::query()->where('payment_id', $payment->getKey())->count())->toBe(1);
});

test('a verified IPN activates the subscription server to server', function () {
    configurePaymentGateway();

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $payment = pendingPaymentFor($user, paymentTestPlan('student'));
    fakeGatewayValidation($payment);

    $this->post(route('payments.sslcommerz.ipn'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-'.$payment->getKey(),
    ])->assertOk()->assertSee('OK');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($user->fresh()->hasActiveSubscription('student'))->toBeTrue();
});

test('a gateway transaction can only activate one subscription', function () {
    configurePaymentGateway();

    $firstUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $secondUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();

    $first = pendingPaymentFor($firstUser, $plan);
    $second = pendingPaymentFor($secondUser, $plan);

    Http::fakeSequence()
        ->push([
            'status' => 'VALID',
            'tran_id' => $first->transaction_id,
            'amount' => (string) $first->amount,
            'currency' => 'BDT',
            'bank_tran_id' => 'BANK-DUPLICATE',
        ])
        ->push([
            'status' => 'VALID',
            'tran_id' => $second->transaction_id,
            'amount' => (string) $second->amount,
            'currency' => 'BDT',
            'bank_tran_id' => 'BANK-DUPLICATE',
        ]);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $first->transaction_id,
        'val_id' => 'VAL-ONE',
    ])->assertRedirect();

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $second->transaction_id,
        'val_id' => 'VAL-TWO',
    ])->assertRedirect();

    expect($first->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($second->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and(UserSubscription::query()->count())->toBe(1);
});

test('a renewal extends the existing paid expiration date', function () {
    configurePaymentGateway();
    $start = now()->setDate(2026, 5, 1)->setTime(10, 0);
    $this->travelTo($start);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();

    $existing = UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => $start,
        'ends_at' => $start->copy()->addDays(10),
    ]);

    $payment = pendingPaymentFor($user, $plan);
    fakeGatewayValidation($payment);

    $this->post(route('payments.sslcommerz.success'), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL-'.$payment->getKey(),
    ])->assertRedirect();

    $renewal = UserSubscription::query()->where('payment_id', $payment->getKey())->firstOrFail();

    expect($renewal->ends_at->getTimestamp())->toBe($existing->ends_at->copy()->addDays(30)->getTimestamp());
});

test('an expired subscription does not grant access while an active one does', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now()->subDays(40),
        'ends_at' => now()->subDay(),
    ]);

    expect($user->fresh()->hasActiveSubscription('student'))->toBeFalse();

    $this->actingAs($user)->get(route('student.dashboard'))->assertRedirect(route('student.subscription.show'));

    UserSubscription::query()->create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => $plan->getKey(),
        'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_MONTHLY,
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    expect($user->fresh()->hasActiveSubscription('student'))->toBeTrue();

    $this->actingAs($user)->get(route('student.dashboard'))->assertRedirect(route('student.messes.index'));
});

test('checkout fails safely when the gateway is not configured', function () {
    config([
        'services.sslcommerz.store_id' => null,
        'services.sslcommerz.store_password' => null,
    ]);

    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = paymentTestPlan();

    $this->actingAs($user)
        ->post(route('subscriptions.checkout', 'student'), ['plan' => $plan->slug])
        ->assertRedirect(route('subscriptions.show', 'student'));

    expect(Payment::query()->firstOrFail()->status)->toBe(Payment::STATUS_FAILED)
        ->and($user->fresh()->hasActiveSubscription('student'))->toBeFalse();
});
