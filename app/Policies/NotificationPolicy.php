<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->getKey();
    }

    public function update(User $user, Notification $notification): bool
    {
        return $notification->notifiable_id === $user->getKey()
            && $notification->notifiable_type === User::class;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $this->update($user, $notification);
    }
}
