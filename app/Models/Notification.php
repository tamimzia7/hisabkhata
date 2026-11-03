<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\DatabaseNotification;

/** @use HasFactory<NotificationFactory> */
class Notification extends DatabaseNotification
{
    use HasFactory;

    /** @return MorphMany<NotificationLog, self> */
    public function logs(): MorphMany
    {
        return $this->morphMany(NotificationLog::class, 'loggable');
    }
}
