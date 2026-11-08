<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mess_id', 'name', 'type', 'opening_balance', 'is_active'])]
class MessAccount extends Model
{
    public const TYPE_CASH = 'cash';

    public const TYPE_BANK = 'bank';

    public const TYPE_BKASH = 'bkash';

    public const TYPE_NAGAD = 'nagad';

    public const TYPES = [
        self::TYPE_CASH,
        self::TYPE_BANK,
        self::TYPE_BKASH,
        self::TYPE_NAGAD,
    ];

    /**
     * The mess the account belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Ledger entries posted to the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(MessTransaction::class, 'account_id');
    }

    /**
     * The account balance derived from the ledger.
     */
    public function balance(): float
    {
        $credits = (float) $this->transactions()->active()->where('direction', MessTransaction::DIRECTION_CREDIT)->sum('amount');
        $debits = (float) $this->transactions()->active()->where('direction', MessTransaction::DIRECTION_DEBIT)->sum('amount');

        return round((float) $this->opening_balance + $credits - $debits, 2);
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
            'is_active' => 'boolean',
        ];
    }
}
