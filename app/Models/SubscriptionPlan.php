<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'product', 'billing_cycle', 'price', 'currency', 'duration_days', 'features', 'is_active'])]
class SubscriptionPlan extends Model
{
    public const PRODUCT_STUDENT = 'student';

    public const BILLING_CYCLE_TRIAL = 'trial';

    public const BILLING_CYCLE_MONTHLY = 'monthly';

    public const BILLING_CYCLE_YEARLY = 'yearly';

    public const BILLING_CYCLE_LEGACY = 'legacy';

    public const CURRENCY_BDT = 'BDT';

    public const TRIAL_DAYS = 30;

    /**
     * Every independent product that can be subscribed to.
     *
     * @var list<string>
     */
    public const PRODUCTS = [
        self::PRODUCT_STUDENT,
    ];

    /**
     * The paid billing cycles shown to clients.
     *
     * @var list<string>
     */
    public const PAID_BILLING_CYCLES = [
        self::BILLING_CYCLE_MONTHLY,
        self::BILLING_CYCLE_YEARLY,
    ];

    /**
     * The users subscribed to this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * The payments made for this plan.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Determine whether the given product identifier is supported.
     */
    public static function isValidProduct(string $product): bool
    {
        return in_array($product, self::PRODUCTS, true);
    }

    /**
     * The localized display name for a product.
     */
    public static function productLabel(string $product): string
    {
        return (string) __('subscription.products.'.$product);
    }

    /**
     * The paid plans for a product, ordered by price.
     *
     * @return Collection<int, self>
     */
    public static function paidPlansFor(string $product)
    {
        return self::query()
            ->where('product', $product)
            ->whereIn('billing_cycle', self::PAID_BILLING_CYCLES)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    /**
     * The trial plan for a product.
     */
    public static function trialPlanFor(string $product): self
    {
        return self::query()->firstOrCreate(
            ['slug' => $product.'-trial'],
            [
                'name' => self::productLabel($product).' — '.__('subscription.trial.name'),
                'product' => $product,
                'billing_cycle' => self::BILLING_CYCLE_TRIAL,
                'price' => 0,
                'currency' => self::CURRENCY_BDT,
                'duration_days' => self::TRIAL_DAYS,
                'features' => ['trial' => true],
                'is_active' => true,
            ],
        );
    }

    /**
     * Determine whether this plan is a trial plan.
     */
    public function isTrial(): bool
    {
        return $this->billing_cycle === self::BILLING_CYCLE_TRIAL;
    }

    /**
     * The duration of the plan in days.
     */
    public function durationInDays(): int
    {
        if ($this->duration_days > 0) {
            return (int) $this->duration_days;
        }

        return match ($this->billing_cycle) {
            self::BILLING_CYCLE_MONTHLY => 30,
            self::BILLING_CYCLE_YEARLY => 365,
            default => self::TRIAL_DAYS,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
