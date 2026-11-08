<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'product', 'price', 'duration_days', 'features', 'is_active'])]
class SubscriptionPlan extends Model
{
    public const PRODUCT_STUDENT = 'student';

    public const PRODUCT_FAMILY = 'family';

    public const PRODUCT_SHOP = 'shop';

    /**
     * The users subscribed to this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
