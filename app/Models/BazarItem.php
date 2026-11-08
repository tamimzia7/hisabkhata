<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bazar_record_id', 'name', 'quantity', 'unit', 'unit_price', 'total_price'])]
class BazarItem extends Model
{
    /**
     * The parent purchase record.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(BazarRecord::class, 'bazar_record_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
