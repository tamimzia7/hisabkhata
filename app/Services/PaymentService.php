<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentService
{
    public function __construct(
        private SslCommerzGateway $gateway,
        private SubscriptionService $subscriptions,
    ) {}

    /**
     * Create (or reuse) a pending payment for a paid plan.
     */
    public function initiate(User $user, SubscriptionPlan $plan): Payment
    {
        return DB::transaction(function () use ($user, $plan): Payment {
            $existing = Payment::query()
                ->where('user_id', $user->getKey())
                ->where('subscription_plan_id', $plan->getKey())
                ->where('status', Payment::STATUS_PENDING)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return Payment::query()->create([
                'user_id' => $user->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'transaction_id' => $this->newTransactionId(),
                'gateway' => Payment::GATEWAY_SSLCOMMERZ,
                'product' => $plan->product,
                'billing_cycle' => $plan->billing_cycle,
                'amount' => $plan->price,
                'currency' => $plan->currency ?: Payment::CURRENCY_BDT,
                'status' => Payment::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Open the hosted SSLCommerz checkout for a pending payment.
     */
    public function redirectToGateway(Payment $payment, User $user): string
    {
        try {
            $result = $this->gateway->initiate($payment, $user);
        } catch (RuntimeException $exception) {
            $this->markFailed($payment, $exception->getMessage());

            throw $exception;
        }

        $payment->update([
            'gateway_status' => $result['response']['status'] ?? null,
            'gateway_page_url' => $result['url'],
            'gateway_response' => $result['response'],
            'initiated_at' => now(),
        ]);

        return $result['url'];
    }

    /**
     * Verify a successful gateway callback and activate the subscription.
     *
     * The gateway validation API is the only source of truth. A request that
     * merely claims success is never trusted.
     */
    public function handleSuccess(Payment $payment, ?string $validationId): Payment
    {
        if ($payment->isSuccessful()) {
            return $payment;
        }

        if (! filled($validationId)) {
            return $this->markFailed($payment, __('subscription.payment.validation_missing'));
        }

        try {
            $validation = $this->gateway->verify((string) $validationId);
        } catch (Throwable) {
            $validation = null;
        }

        if ($validation === null) {
            return $this->markFailed($payment, __('subscription.payment.validation_failed'));
        }

        try {
            return DB::transaction(function () use ($payment, $validation): Payment {
                $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

                // Idempotent: duplicate callbacks/IPN must never create a second subscription.
                if (filled($locked->user_subscription_id)) {
                    return $locked;
                }

                $reason = $this->validationFailureReason($locked, $validation);

                if ($reason !== null) {
                    $locked->update([
                        'status' => Payment::STATUS_FAILED,
                        'gateway_status' => (string) ($validation['status'] ?? 'INVALID'),
                        'validation_response' => $validation,
                        'failure_reason' => $reason,
                        'failed_at' => now(),
                    ]);

                    return $locked;
                }

                $bankTransactionId = (string) ($validation['bank_tran_id'] ?? $validation['tran_id'] ?? '');

                if ($bankTransactionId !== '' && Payment::query()
                    ->where('gateway_transaction_id', $bankTransactionId)
                    ->whereKeyNot($locked->getKey())
                    ->exists()) {
                    $locked->update([
                        'status' => Payment::STATUS_FAILED,
                        'gateway_status' => (string) ($validation['status'] ?? 'DUPLICATE'),
                        'validation_response' => $validation,
                        'failure_reason' => __('subscription.payment.duplicate_gateway_transaction'),
                        'failed_at' => now(),
                    ]);

                    return $locked;
                }

                $subscription = $this->subscriptions->activatePaid($locked);

                $locked->update([
                    'user_subscription_id' => $subscription->getKey(),
                    'status' => Payment::STATUS_SUCCESSFUL,
                    'gateway_status' => (string) ($validation['status'] ?? null),
                    'gateway_transaction_id' => $bankTransactionId !== '' ? $bankTransactionId : null,
                    'payment_method' => $this->paymentMethod($validation),
                    'validation_response' => $validation,
                    'paid_at' => now(),
                    'ipn_verified_at' => now(),
                    'failure_reason' => null,
                    'failed_at' => null,
                ]);

                return $locked->fresh();
            });
        } catch (QueryException) {
            // A concurrent callback may have already won the database-level
            // uniqueness check. Never activate a second subscription.
            $fresh = $payment->fresh();

            return $fresh?->isSuccessful() === true
                ? $fresh
                : $this->markFailed($fresh ?? $payment, __('subscription.payment.duplicate_gateway_transaction'));
        }
    }

    /**
     * Mark a payment as failed without touching subscription history.
     */
    public function markFailed(Payment $payment, string $reason, array $response = []): Payment
    {
        if ($payment->isSuccessful()) {
            return $payment;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => Str::limit($reason, 250),
            'gateway_response' => $response ?: $payment->gateway_response,
            'failed_at' => now(),
        ]);

        return $payment->fresh();
    }

    /**
     * Mark a payment as cancelled without touching subscription history.
     */
    public function markCancelled(Payment $payment, string $reason, array $response = []): Payment
    {
        if ($payment->isSuccessful()) {
            return $payment;
        }

        $payment->update([
            'status' => Payment::STATUS_CANCELLED,
            'failure_reason' => Str::limit($reason, 250),
            'gateway_response' => $response ?: $payment->gateway_response,
            'failed_at' => now(),
        ]);

        return $payment->fresh();
    }

    /**
     * Find a payment by our local transaction identifier.
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return Payment::query()->where('transaction_id', $transactionId)->first();
    }

    /**
     * A unique, locally generated transaction identifier.
     */
    private function newTransactionId(): string
    {
        do {
            $transactionId = 'HK'.now()->format('ymdHis').Str::upper(Str::random(10));
        } while (Payment::query()->where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    /**
     * Validate that the gateway response belongs to this exact payment.
     *
     * @param  array<string, mixed>  $validation
     */
    private function validationFailureReason(Payment $payment, array $validation): ?string
    {
        if ((string) ($validation['tran_id'] ?? '') !== $payment->transaction_id) {
            return __('subscription.payment.transaction_mismatch');
        }

        if (strtoupper((string) ($validation['currency'] ?? '')) !== strtoupper((string) $payment->currency)) {
            return __('subscription.payment.currency_mismatch');
        }

        if (abs((float) ($validation['amount'] ?? 0) - (float) $payment->amount) > 0.009) {
            return __('subscription.payment.amount_mismatch');
        }

        return null;
    }

    /**
     * Extract the payment method shown in history.
     *
     * @param  array<string, mixed>  $validation
     */
    private function paymentMethod(array $validation): ?string
    {
        $method = $validation['card_type'] ?? $validation['card_issuer'] ?? null;

        return filled($method) ? Str::limit((string) $method, 50, '') : null;
    }
}
