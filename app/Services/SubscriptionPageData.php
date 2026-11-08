<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use InvalidArgumentException;

class SubscriptionPageData
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * The complete subscription page payload for one product.
     *
     * @return array<string, mixed>
     */
    public function forProduct(User $user, string $product): array
    {
        if (! SubscriptionPlan::isValidProduct($product)) {
            throw new InvalidArgumentException(__('subscription.errors.invalid_product'));
        }

        $active = $user->activeSubscription($product);
        $latest = $user->subscriptions()
            ->with('plan')
            ->forProduct($product)
            ->latest('id')
            ->first();
        $trial = $user->subscriptionTrial($product);
        $latestPayment = $user->payments()
            ->where('product', $product)
            ->latest('id')
            ->first();

        return [
            'product' => $product,
            'productName' => SubscriptionPlan::productLabel($product),
            'activeSubscription' => $active,
            'currentSubscription' => $active ?? $latest,
            'trial' => $trial,
            'trialEligible' => $this->subscriptions->isTrialEligible($user, $product),
            'trialRemainingDays' => $trial !== null && $trial->isActive() ? $trial->remainingDays() : 0,
            'remainingDays' => ($active ?? $latest)?->remainingDays() ?? 0,
            'plans' => SubscriptionPlan::paidPlansFor($product),
            'payments' => $user->payments()
                ->where('product', $product)
                ->latest('id')
                ->take(10)
                ->get(),
            'latestPayment' => $latestPayment,
            'currency' => SubscriptionPlan::CURRENCY_BDT,
            'trialDays' => SubscriptionPlan::TRIAL_DAYS,
        ];
    }

    /**
     * The subscription summary for every product.
     *
     * @return list<array<string, mixed>>
     */
    public function allProducts(User $user): array
    {
        return array_map(
            fn (string $product): array => $this->forProduct($user, $product),
            SubscriptionPlan::PRODUCTS,
        );
    }
}
