<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mess_id', 'account_id', 'created_by', 'type', 'direction', 'amount',
    'transaction_date', 'reference_type', 'reference_id', 'description', 'meta', 'status',
])]
class MessTransaction extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Limit the query to ledger entries that affect balances.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_BAZAR = 'bazar';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    /**
     * The mess the ledger entry belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The account affected by the entry.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MessAccount::class, 'account_id');
    }

    /**
     * The user who posted the entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine whether the entry is a transfer between accounts.
     */
    public function isTransfer(): bool
    {
        return in_array($this->type, [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT], true);
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
            'transaction_date' => 'date',
            'meta' => 'array',
        ];
    }
}
