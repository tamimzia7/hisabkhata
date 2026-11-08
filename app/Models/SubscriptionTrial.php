<?php

namespace App\Models;

use Database\Factories\SubscriptionTrialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product', 'starts_at', 'ends_at'])]
class SubscriptionTrial extends Model
{
    /** @use HasFactory<SubscriptionTrialFactory> */
    use HasFactory;

    /**
     * The user who used the trial.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether the trial is currently running.
     */
    public function isActive(): bool
    {
        return ! $this->starts_at->isFuture() && $this->ends_at->isFuture();
    }

    /**
     * Determine whether the trial has already expired.
     */
    public function isExpired(): bool
    {
        return ! $this->ends_at->isFuture();
    }

    /**
     * The number of whole/partial days left in the trial.
     */
    public function remainingDays(): int
    {
        $seconds = $this->ends_at->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($seconds / 86400));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
