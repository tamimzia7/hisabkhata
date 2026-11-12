<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use InvalidArgumentException;

class SubscriptionPageData
{
    /**
     * Presentation metadata for each independent product.
     *
     * @var array<string, array{icon: string, audience: string, description: string, features: list<string>}>
     */
    private const CATALOG = [
        SubscriptionPlan::PRODUCT_STUDENT => [
            'icon' => 'utensils',
            'audience' => 'subscription.catalog.student.audience',
            'description' => 'subscription.catalog.student.description',
            'features' => [
                'subscription.features.member_management',
                'subscription.features.meal_tracking',
                'subscription.features.bazar',
                'subscription.features.member_balance',
                'subscription.features.reports',
            ],
        ],
        SubscriptionPlan::PRODUCT_FAMILY => [
            'icon' => 'users',
            'audience' => 'subscription.catalog.family.audience',
            'description' => 'subscription.catalog.family.description',
            'features' => [
                'subscription.features.income',
                'subscription.features.expense_management',
                'subscription.features.budget',
                'subscription.features.savings',
            ],
        ],
        SubscriptionPlan::PRODUCT_SHOP => [
            'icon' => 'store',
            'audience' => 'subscription.catalog.shop.audience',
            'description' => 'subscription.catalog.shop.description',
            'features' => [
                'subscription.features.shop_sales',
                'subscription.features.shop_invoices',
                'subscription.features.shop_dues',
                'subscription.features.shop_profit',
            ],
        ],
    ];

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

        return $this->buildProducts($user)[$product];
    }

    /**
     * The subscription summary for every product.
     *
     * @return list<array<string, mixed>>
     */
    public function allProducts(User $user): array
    {
        return array_values($this->buildProducts($user));
    }

    /**
     * Aggregate the overview counters from the product payloads.
     *
     * @param  list<array<string, mixed>>  $products
     * @return array{active: int, trialEligible: int, upcoming: int, windowDays: int}
     */
    public function overview(array $products): array
    {
        $windowDays = 7;
        $limit = now()->addDays($windowDays);
        $active = 0;
        $trialEligible = 0;
        $upcoming = 0;

        foreach ($products as $item) {
            if (in_array($item['status'], ['active', 'trial'], true)) {
                $active++;
            }

            if ($item['trialEligible']) {
                $trialEligible++;
            }

            $subscription = $item['activeSubscription'];

            if ($subscription?->ends_at !== null
                && $subscription->ends_at->isFuture()
                && $subscription->ends_at->lessThanOrEqualTo($limit)) {
                $upcoming++;
            }
        }

        return [
            'active' => $active,
            'trialEligible' => $trialEligible,
            'upcoming' => $upcoming,
            'windowDays' => $windowDays,
        ];
    }

    /**
     * Build every product payload using eager-loaded collections.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildProducts(User $user): array
    {
        $subscriptions = $user->subscriptions()->with('plan')->get();
        $trials = $user->subscriptionTrials()->orderBy('id')->get()->keyBy('product');
        $payments = $user->payments()->latest('id')->get()->groupBy('product');
        $plans = SubscriptionPlan::query()
            ->whereIn('billing_cycle', SubscriptionPlan::PAID_BILLING_CYCLES)
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->groupBy('product');

        $products = [];

        foreach (SubscriptionPlan::PRODUCTS as $product) {
            $productSubscriptions = $subscriptions
                ->filter(fn (UserSubscription $subscription): bool => $subscription->plan?->product === $product)
                ->values();

            $active = $productSubscriptions
                ->filter(fn (UserSubscription $subscription): bool => $subscription->isActive())
                ->sortByDesc(fn (UserSubscription $subscription): int => $subscription->ends_at?->getTimestamp() ?? PHP_INT_MAX)
                ->first();

            $latest = $productSubscriptions->sortByDesc('id')->first();
            $current = $active ?? $latest;
            $trial = $trials->get($product);
            $productPayments = $payments->get($product, collect());
            $latestPayment = $productPayments->first();
            $productPlans = $plans->get($product, collect());
            $monthlyPlan = $productPlans->firstWhere('billing_cycle', SubscriptionPlan::BILLING_CYCLE_MONTHLY);
            $yearlyPlan = $productPlans->firstWhere('billing_cycle', SubscriptionPlan::BILLING_CYCLE_YEARLY);
            $trialEligible = $trial === null && $active === null;
            $status = $this->resolveStatus($active, $latest, $latestPayment);
            $catalog = self::CATALOG[$product];

            $products[$product] = [
                'product' => $product,
                'productName' => SubscriptionPlan::productLabel($product),
                'icon' => $catalog['icon'],
                'audience' => __($catalog['audience']),
                'description' => __($catalog['description']),
                'features' => array_map(fn (string $key): string => __($key), $catalog['features']),
                'activeSubscription' => $active,
                'currentSubscription' => $current,
                'trial' => $trial,
                'trialEligible' => $trialEligible,
                'trialRemainingDays' => $trial !== null && $trial->isActive() ? $trial->remainingDays() : 0,
                'remainingDays' => $current?->remainingDays() ?? 0,
                'plans' => $productPlans->values(),
                'monthlyPlan' => $monthlyPlan,
                'yearlyPlan' => $yearlyPlan,
                'monthlyPrice' => $monthlyPlan?->price,
                'yearlyPrice' => $yearlyPlan?->price,
                'annualSavings' => $this->annualSavings($monthlyPlan, $yearlyPlan),
                'payments' => $productPayments->take(10),
                'latestPayment' => $latestPayment,
                'status' => $status,
                'cta' => $this->ctaFor($product, $status, $trialEligible),
                'secondaryCta' => $status === 'inactive' && $trialEligible
                    ? ['label' => __('subscription.cta.explore'), 'url' => route('subscriptions.show', $product)]
                    : null,
                'currency' => SubscriptionPlan::CURRENCY_BDT,
                'trialDays' => SubscriptionPlan::TRIAL_DAYS,
            ];
        }

        return $products;
    }

    /**
     * Resolve the user-facing status for a product.
     */
    private function resolveStatus(?UserSubscription $active, ?UserSubscription $latest, ?Payment $latestPayment): string
    {
        if ($active !== null) {
            return $active->isTrial() ? 'trial' : 'active';
        }

        if ($latest !== null) {
            if ($latest->status === UserSubscription::STATUS_SUSPENDED) {
                return 'suspended';
            }

            if ($latest->status === UserSubscription::STATUS_CANCELLED) {
                return 'cancelled';
            }

            if ($latest->status === UserSubscription::STATUS_EXPIRED || $latest->isExpired()) {
                return 'expired';
            }
        }

        if ($latestPayment?->status === Payment::STATUS_PENDING) {
            return 'pending';
        }

        return 'inactive';
    }

    /**
     * The primary call to action for the current state.
     *
     * @return array{label: string, url: string, method: string}
     */
    private function ctaFor(string $product, string $status, bool $trialEligible): array
    {
        $showUrl = route('subscriptions.show', $product);

        return match ($status) {
            'active', 'trial', 'suspended' => [
                'label' => __('subscription.cta.manage'),
                'url' => $showUrl,
                'method' => 'link',
            ],
            'expired' => [
                'label' => __('subscription.cta.renew'),
                'url' => $showUrl,
                'method' => 'link',
            ],
            'cancelled', 'pending' => [
                'label' => __('subscription.cta.view_plans'),
                'url' => $showUrl,
                'method' => 'link',
            ],
            default => $trialEligible
                ? [
                    'label' => __('subscription.cta.start_trial'),
                    'url' => route('subscriptions.trial', $product),
                    'method' => 'post',
                ]
                : [
                    'label' => __('subscription.cta.explore'),
                    'url' => $showUrl,
                    'method' => 'link',
                ],
        };
    }

    /**
     * The genuine annual saving based on configured prices.
     */
    private function annualSavings(?SubscriptionPlan $monthlyPlan, ?SubscriptionPlan $yearlyPlan): float
    {
        if ($monthlyPlan === null || $yearlyPlan === null) {
            return 0.0;
        }

        return max(0.0, (float) $monthlyPlan->price * 12 - (float) $yearlyPlan->price);
    }
}
