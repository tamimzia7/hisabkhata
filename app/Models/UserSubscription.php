<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subscription_plan_id',
    'billing_cycle',
    'subscription_trial_id',
    'payment_id',
    'status',
    'starts_at',
    'ends_at',
    'cancelled_at',
    'suspended_at',
    'suspended_by',
    'suspension_reason',
])]
class UserSubscription extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * The user subscribed to the plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The subscribed plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * The permanent trial record that created this subscription.
     */
    public function trial(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTrial::class, 'subscription_trial_id');
    }

    /**
     * The verified payment that activated this subscription.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * The admin who suspended this subscription.
     */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Limit the query to currently valid subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('suspended_at')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', now()));
    }

    /**
     * Limit the query to a specific product.
     */
    public function scopeForProduct(Builder $query, string $product): Builder
    {
        return $query->whereHas('plan', fn (Builder $query): Builder => $query->where('product', $product));
    }

    /**
     * Determine whether the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->suspended_at === null
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Determine whether the subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at !== null && ! $this->ends_at->isFuture());
    }

    /**
     * Determine whether this access was granted by a free trial.
     */
    public function isTrial(): bool
    {
        return $this->subscription_trial_id !== null
            || $this->billing_cycle === SubscriptionPlan::BILLING_CYCLE_TRIAL
            || $this->plan?->isTrial() === true;
    }

    /**
     * The product this subscription belongs to.
     */
    public function product(): ?string
    {
        return $this->plan?->product;
    }

    /**
     * The number of whole/partial days left before expiration.
     */
    public function remainingDays(): int
    {
        if ($this->ends_at === null) {
            return 0;
        }

        $seconds = $this->ends_at->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($seconds / 86400));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }
}
