<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mess_id', 'mess_member_id', 'created_by', 'updated_by', 'entry_date',
    'breakfast', 'lunch', 'dinner', 'note', 'status',
])]
class MealEntry extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_CANCELLED,
    ];

    /**
     * Limit the query to entries that count towards the meal rate.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * The mess the entry belongs to.
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * The member whose meals were recorded.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(MessMember::class, 'mess_member_id');
    }

    /**
     * The user who recorded the entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who last modified the entry.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'breakfast' => 'decimal:2',
            'lunch' => 'decimal:2',
            'dinner' => 'decimal:2',
            'total_meals' => 'decimal:2',
        ];
    }
}
