<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @use HasFactory<NotificationFactory> */
class NotificationLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['loggable_id', 'loggable_type', 'data'];

    protected function casts(): array
    {
        return [
            'loggable_id' => 'string',
            'loggable_type' => 'string',
            'data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return MorphMany<Notification, self> */
    public function notification(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
