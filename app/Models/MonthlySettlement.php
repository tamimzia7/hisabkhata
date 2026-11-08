<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'mess_id', 'month', 'version', 'status', 'total_deposits', 'total_refunds',
    'total_adjustments', 'total_bazar', 'total_meal_expenses', 'total_other_expenses',
    'total_personal_expenses', 'total_meals', 'total_meal_cost', 'meal_rate',
    'total_surplus', 'total_outstanding_dues', 'total_receivable', 'finalized_at',
    'finalized_by', 'reopened_at', 'reopened_by', 'reopen_reason',
])]
class MonthlySettlement extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_SUPERSEDED = 'superseded';

    /**
     * The mess the settlement belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Member-level settlement snapshot rows.
     */
    public function details(): HasMany
    {
        return $this->hasMany(SettlementDetail::class);
    }

    /**
     * The user who finalized the settlement.
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * The user who reopened the settlement.
     */
    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Determine whether the settlement is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_deposits' => 'decimal:2',
            'total_refunds' => 'decimal:2',
            'total_adjustments' => 'decimal:2',
            'total_bazar' => 'decimal:2',
            'total_meal_expenses' => 'decimal:2',
            'total_other_expenses' => 'decimal:2',
            'total_personal_expenses' => 'decimal:2',
            'total_meals' => 'decimal:2',
            'total_meal_cost' => 'decimal:2',
            'meal_rate' => 'decimal:4',
            'total_surplus' => 'decimal:2',
            'total_outstanding_dues' => 'decimal:2',
            'total_receivable' => 'decimal:2',
            'finalized_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }
}
