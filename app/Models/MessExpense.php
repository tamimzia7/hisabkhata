<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mess_id', 'mess_member_id', 'account_id', 'created_by', 'expense_date', 'title', 'amount',
    'category', 'is_meal_related', 'is_personal', 'allocation_method', 'notes', 'receipt_path', 'status',
])]
class MessExpense extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOCATION_EQUAL = 'equal';

    public const ALLOCATION_MEALS = 'meals';

    public const ALLOCATIONS = [
        self::ALLOCATION_EQUAL,
        self::ALLOCATION_MEALS,
    ];

    /**
     * Limit the query to expenses that count financially.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * The member a personal expense belongs to.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MessMember::class, 'mess_member_id');
    }

    /**
     * The mess the expense belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The account the expense was paid from.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MessAccount::class, 'account_id');
    }

    /**
     * The user who recorded the expense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'is_meal_related' => 'boolean',
            'is_personal' => 'boolean',
        ];
    }
}
