<?php

namespace Database\Factories;

use App\Models\SubscriptionTrial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionTrial>
 */
class SubscriptionTrialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product' => 'student',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ];
    }
}
