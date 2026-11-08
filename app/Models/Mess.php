<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'owner_id', 'name', 'address', 'description', 'start_date', 'billing_month',
    'monthly_budget', 'contact_name', 'contact_phone', 'contact_email', 'status',
])]
class Mess extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * The workspace owner.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Every member of the mess.
     */
    public function members(): HasMany
    {
        return $this->hasMany(MessMember::class);
    }

    /**
     * Only active members of the mess.
     */
    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', MessMember::STATUS_ACTIVE);
    }

    /**
     * Meal entries recorded in the mess.
     */
    public function mealEntries(): HasMany
    {
        return $this->hasMany(MealEntry::class);
    }

    /**
     * Bazar purchase records.
     */
    public function bazarRecords(): HasMany
    {
        return $this->hasMany(BazarRecord::class);
    }

    /**
     * Member deposits, refunds, and adjustments.
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(MessDeposit::class);
    }

    /**
     * Non-bazar expenses.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(MessExpense::class);
    }

    /**
     * Money accounts belonging to the mess.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(MessAccount::class);
    }

    /**
     * The financial ledger of the mess.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(MessTransaction::class);
    }

    /**
     * Monthly settlements, including historical versions.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(MonthlySettlement::class);
    }

    /**
     * Member invitations.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(MessInvitation::class);
    }

    /**
     * Audit trail of the mess.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(MessAuditLog::class);
    }

    /**
     * Find the mess membership for the given user.
     */
    public function memberFor(User $user): ?MessMember
    {
        return $this->members()->where('user_id', $user->getKey())->first();
    }

    /**
     * The role the given user holds inside the mess, if any.
     */
    public function roleFor(User $user): ?string
    {
        return $this->memberFor($user)?->role;
    }

    /**
     * Determine whether the given user belongs to the mess.
     */
    public function hasMember(User $user): bool
    {
        return $this->memberFor($user) !== null;
    }

    /**
     * The latest settlement for the given billing month.
     */
    public function settlementForMonth(string $month): ?MonthlySettlement
    {
        return $this->settlements()
            ->where('month', $month)
            ->latest('version')
            ->first();
    }

    /**
     * The most recent finalized settlement for the given month.
     */
    public function finalizedSettlementForMonth(string $month): ?MonthlySettlement
    {
        return $this->settlements()
            ->where('month', $month)
            ->where('status', MonthlySettlement::STATUS_FINALIZED)
            ->latest('version')
            ->first();
    }

    /**
     * The latest settlement overall.
     */
    public function latestSettlement(): HasOne
    {
        return $this->hasOne(MonthlySettlement::class)->latestOfMany();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'monthly_budget' => 'decimal:2',
        ];
    }
}
