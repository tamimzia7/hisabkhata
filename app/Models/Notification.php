<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @use HasFactory<NotificationFactory> */
class Notification extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['notifiable_id', 'notifiable_type', 'data'];

    protected function casts(): array
    {
        return [
            'notifiable_id' => 'string',
            'notifiable_type' => 'integer',
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return MorphMany<NotificationLog, self> */
    public function logs(): MorphMany
    {
        return $this->morphMany(NotificationLog::class, 'loggable');
    }
}
