<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'mess_id', 'user_id', 'name', 'email', 'phone', 'photo_path', 'joining_date',
    'status', 'role', 'emergency_contact', 'permissions',
])]
class MessMember extends Model
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ACCOUNTANT = 'accountant';

    public const ROLE_MEMBER = 'member';

    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_MANAGER,
        self::ROLE_ACCOUNTANT,
        self::ROLE_MEMBER,
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * The mess the member belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The linked user account, if one exists.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Meal entries recorded by the member.
     */
    public function mealEntries(): HasMany
    {
        return $this->hasMany(MealEntry::class);
    }

    /**
     * Deposits made by the member.
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(MessDeposit::class);
    }

    /**
     * Settlement detail rows belonging to the member.
     */
    public function settlementDetails(): HasMany
    {
        return $this->hasMany(SettlementDetail::class);
    }

    /**
     * Determine whether the member is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Determine whether the member owns the mess.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Determine whether the member may manage members, meals, and bazar.
     */
    public function canManageOperations(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_MANAGER], true);
    }

    /**
     * Determine whether the member may manage finance.
     */
    public function canManageFinance(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ACCOUNTANT], true);
    }

    /**
     * Determine whether the member may perform the given ability.
     */
    public function allows(string $ability): bool
    {
        if ($this->role === self::ROLE_OWNER) {
            return true;
        }

        $defaults = [
            'manage_members' => $this->role === self::ROLE_MANAGER,
            'manage_meals' => $this->role === self::ROLE_MANAGER,
            'manage_bazar' => $this->role === self::ROLE_MANAGER,
            'manage_finance' => $this->role === self::ROLE_ACCOUNTANT,
            'manage_settlements' => $this->role === self::ROLE_ACCOUNTANT,
            'view_reports' => true,
        ];

        $overrides = is_array($this->permissions) ? $this->permissions : [];

        if (array_key_exists($ability, $overrides)) {
            return (bool) $overrides[$ability];
        }

        return $defaults[$ability] ?? false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'permissions' => 'array',
        ];
    }
}
