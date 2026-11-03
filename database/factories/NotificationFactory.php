<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Notification> */
class NotificationFactory extends Factory
{
    /** {@inheritdoc} */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'type' => SystemNotification::class,
            'notifiable_id' => User::factory(),
            'notifiable_type' => User::class,
            'data' => [
                'title' => fake()->sentence(),
                'body' => fake()->paragraph(),
                'action_url' => fake()->optional()->url(),
                'level' => 'info',
            ],
            'read_at' => null,
        ];
    }
}
