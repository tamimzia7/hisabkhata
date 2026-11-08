<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'mess_id', 'purchased_by_member_id', 'account_id', 'created_by', 'purchase_date',
    'category', 'payment_source', 'total_amount', 'notes', 'receipt_path', 'status',
])]
class BazarRecord extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Limit the query to bazar records that count financially.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * The mess the purchase belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The member who purchased the items.
     */
    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(MessMember::class, 'purchased_by_member_id');
    }

    /**
     * The account the purchase was paid from.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MessAccount::class, 'account_id');
    }

    /**
     * The user who recorded the purchase.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Line items of the purchase.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BazarItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }
}
