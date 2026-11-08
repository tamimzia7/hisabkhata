<?php

namespace App\Services;

use App\Models\AdminActivity;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTrial;
use App\Models\User;
use App\Models\UserSubscription;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Determine whether a user may still claim the trial for a product.
     */
    public function isTrialEligible(User $user, string $product): bool
    {
        return SubscriptionPlan::isValidProduct($product)
            && ! $user->hasUsedTrial($product)
            && ! $user->hasActiveSubscription($product);
    }

    /**
     * Start the one-time 30-day trial for a product.
     *
     * @throws DomainException
     */
    public function startTrial(User $user, string $product): UserSubscription
    {
        if (! SubscriptionPlan::isValidProduct($product)) {
            throw new DomainException(__('subscription.errors.invalid_product'));
        }

        try {
            return DB::transaction(function () use ($user, $product): UserSubscription {
                $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                if ($lockedUser->hasUsedTrial($product)) {
                    throw new DomainException(__('subscription.errors.trial_already_used'));
                }

                if ($lockedUser->hasActiveSubscription($product)) {
                    throw new DomainException(__('subscription.errors.active_subscription_exists'));
                }

                $startsAt = now();
                $endsAt = $startsAt->copy()->addDays(SubscriptionPlan::TRIAL_DAYS);

                $trial = SubscriptionTrial::query()->create([
                    'user_id' => $lockedUser->getKey(),
                    'product' => $product,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                $plan = SubscriptionPlan::trialPlanFor($product);

                return UserSubscription::query()->create([
                    'user_id' => $lockedUser->getKey(),
                    'subscription_plan_id' => $plan->getKey(),
                    'billing_cycle' => SubscriptionPlan::BILLING_CYCLE_TRIAL,
                    'subscription_trial_id' => $trial->getKey(),
                    'status' => UserSubscription::STATUS_ACTIVE,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);
            });
        } catch (QueryException $exception) {
            // The unique (user_id, product) index is the final race-condition guard.
            throw new DomainException(__('subscription.errors.trial_already_used'), previous: $exception);
        }
    }

    /**
     * Activate a paid subscription after the gateway has verified a payment.
     *
     * Existing paid access is extended rather than discarded.
     */
    public function activatePaid(Payment $payment): UserSubscription
    {
        return DB::transaction(function () use ($payment): UserSubscription {
            $lockedPayment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if (filled($lockedPayment->user_subscription_id)) {
                return UserSubscription::query()->findOrFail($lockedPayment->user_subscription_id);
            }

            $plan = SubscriptionPlan::query()->findOrFail($lockedPayment->subscription_plan_id);
            $user = User::query()->findOrFail($lockedPayment->user_id);
            $current = $user->activeSubscription($plan->product);

            $startsAt = now();
            $base = $startsAt->copy();

            if ($current?->ends_at?->isFuture()) {
                $base = $current->ends_at->copy();
            }

            $subscription = UserSubscription::query()->create([
                'user_id' => $user->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'billing_cycle' => $plan->billing_cycle,
                'payment_id' => $lockedPayment->getKey(),
                'status' => UserSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $base->addDays($plan->durationInDays()),
            ]);

            $lockedPayment->update([
                'user_subscription_id' => $subscription->getKey(),
            ]);

            return $subscription;
        });
    }

    /**
     * Suspend a subscription without deleting any history.
     */
    public function suspend(UserSubscription $subscription, User $admin, ?string $reason = null, ?string $ipAddress = null): UserSubscription
    {
        return DB::transaction(function () use ($subscription, $admin, $reason, $ipAddress): UserSubscription {
            $locked = UserSubscription::query()->whereKey($subscription->getKey())->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => UserSubscription::STATUS_SUSPENDED,
                'suspended_at' => now(),
                'suspended_by' => $admin->getKey(),
                'suspension_reason' => $reason,
            ]);

            $this->logAdminActivity($admin, 'subscription_suspended', $this->subscriptionSubject($locked), $ipAddress);

            return $locked;
        });
    }

    /**
     * Reactivate a suspended subscription if it has not expired yet.
     */
    public function reactivate(UserSubscription $subscription, User $admin, ?string $ipAddress = null): UserSubscription
    {
        return DB::transaction(function () use ($subscription, $admin, $ipAddress): UserSubscription {
            $locked = UserSubscription::query()->whereKey($subscription->getKey())->lockForUpdate()->firstOrFail();

            $status = $locked->ends_at !== null && $locked->ends_at->isPast()
                ? UserSubscription::STATUS_EXPIRED
                : UserSubscription::STATUS_ACTIVE;

            $locked->update([
                'status' => $status,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
            ]);

            $this->logAdminActivity($admin, 'subscription_reactivated', $this->subscriptionSubject($locked), $ipAddress);

            return $locked;
        });
    }

    /**
     * Mark elapsed subscriptions as expired. Access is already denied by the
     * active scope, so this keeps reporting and admin views accurate.
     */
    public function expireDueSubscriptions(): int
    {
        return UserSubscription::query()
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->whereNull('suspended_at')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => UserSubscription::STATUS_EXPIRED, 'updated_at' => now()]);
    }

    /**
     * Record an auditable admin action.
     */
    public function logAdminActivity(User $admin, string $action, string $subject, ?string $ipAddress = null): AdminActivity
    {
        return AdminActivity::query()->create([
            'user_id' => $admin->getKey(),
            'action' => $action,
            'subject' => $subject,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * A human-readable subject for subscription audit entries.
     */
    private function subscriptionSubject(UserSubscription $subscription): string
    {
        $email = $subscription->user?->email ?? 'deleted user';

        return 'Subscription #'.$subscription->getKey().' ('.$email.')';
    }
}
