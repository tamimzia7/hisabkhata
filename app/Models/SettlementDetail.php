<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'monthly_settlement_id', 'mess_member_id', 'opening_balance', 'meals', 'meal_cost',
    'deposits', 'refunds', 'other_allocated', 'personal_expenses', 'adjustments', 'balance',
])]
class SettlementDetail extends Model
{
    /**
     * The parent settlement.
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(MonthlySettlement::class, 'monthly_settlement_id');
    }

    /**
     * The member the row belongs to.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MessMember::class, 'mess_member_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'meals' => 'decimal:2',
            'meal_cost' => 'decimal:2',
            'deposits' => 'decimal:2',
            'refunds' => 'decimal:2',
            'other_allocated' => 'decimal:2',
            'personal_expenses' => 'decimal:2',
            'adjustments' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }
}
