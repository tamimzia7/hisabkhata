<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    /**
     * Create a new database notification.
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public string $level = 'info',
        public ?string $icon = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'level' => $this->level,
            'icon' => $this->icon,
        ], fn (mixed $value): bool => $value !== null);
    }
}
