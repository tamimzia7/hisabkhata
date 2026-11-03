<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * Send a notification to one user.
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $level = 'info',
        ?string $icon = null,
    ): void {
        $user->notify(new SystemNotification($title, $body, $actionUrl, $level, $icon));
    }

    /**
     * Send a notification to several users.
     *
     * @param  iterable<int, User>  $users
     */
    public function sendToUsers(
        iterable $users,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $level = 'info',
        ?string $icon = null,
    ): int {
        $recipients = Collection::make($users);

        NotificationFacade::send(
            $recipients,
            new SystemNotification($title, $body, $actionUrl, $level, $icon),
        );

        return $recipients->count();
    }

    /**
     * Send a notification to every user holding one of the given roles.
     *
     * @param  list<string>  $roles
     */
    public function sendToRoles(
        array $roles,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $level = 'info',
        ?string $icon = null,
    ): int {
        $recipients = User::query()->whereIn('role', $roles)->get();

        return $this->sendToUsers($recipients, $title, $body, $actionUrl, $level, $icon);
    }

    /**
     * Send a notification to every user in the application.
     */
    public function sendToAll(
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $level = 'info',
        ?string $icon = null,
    ): int {
        return $this->sendToUsers(
            User::query()->get(),
            $title,
            $body,
            $actionUrl,
            $level,
            $icon,
        );
    }
}
