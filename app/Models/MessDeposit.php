<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mess_id', 'mess_member_id', 'account_id', 'created_by', 'deposit_date',
    'amount', 'type', 'payment_method', 'reference', 'notes', 'status',
])]
class MessDeposit extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [
        self::TYPE_DEPOSIT,
        self::TYPE_REFUND,
        self::TYPE_ADJUSTMENT,
    ];

    /**
     * Limit the query to deposits that count financially.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * The mess the deposit belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The member who made the deposit.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MessMember::class, 'mess_member_id');
    }

    /**
     * The account the money was received into.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MessAccount::class, 'account_id');
    }

    /**
     * The user who recorded the deposit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The signed effect of the deposit on the member balance.
     *
     * Refunds and adjustments are handled separately by the settlement engine.
     */
    public function signedAmount(): float
    {
        return match ($this->type) {
            self::TYPE_REFUND => -1 * (float) $this->amount,
            default => (float) $this->amount,
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
            'deposit_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
