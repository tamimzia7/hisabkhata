<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subscription_plan_id',
    'user_subscription_id',
    'transaction_id',
    'gateway',
    'gateway_transaction_id',
    'product',
    'billing_cycle',
    'amount',
    'currency',
    'status',
    'gateway_status',
    'payment_method',
    'gateway_page_url',
    'gateway_response',
    'validation_response',
    'failure_reason',
    'initiated_at',
    'paid_at',
    'failed_at',
    'ipn_verified_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const GATEWAY_SSLCOMMERZ = 'sslcommerz';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const CURRENCY_BDT = 'BDT';

    /**
     * The user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The plan that was purchased.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * The subscription activated by this payment.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    /**
     * Limit the query to successful payments.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESSFUL);
    }

    /**
     * Determine whether the payment is still awaiting verification.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Determine whether the payment has been verified and activated.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'validation_response' => 'array',
            'initiated_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'ipn_verified_at' => 'datetime',
        ];
    }
}
