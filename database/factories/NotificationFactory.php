<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Notification> */
class NotificationFactory extends Factory
{
    /** {@inheritdoc} */
    public function definition(): array
    {
        return [
            'type' => 'notification',
            'notifiable_id' => fake()->unique()->sha1(),
            'notifiable_type' => 1,
            'data' => [
                'title' => fake()->sentence(),
                'body' => fake()->paragraph(),
                'action_url' => fake()->optional()->url(),
                'action_label' => fake()->word(),
                'level' => 'info',
            ],
            'read_at' => null,
        ];
    }
}
