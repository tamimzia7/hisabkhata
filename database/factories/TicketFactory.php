<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => Ticket::NUMBER_PREFIX.str_pad(
                (string) $this->faker->unique()->numberBetween(1, 999999),
                6,
                '0',
                STR_PAD_LEFT,
            ),
            'user_id' => User::factory(),
            'assignee_id' => null,
            'subject' => $this->faker->sentence(4),
            'category' => $this->faker->randomElement(Ticket::CATEGORIES),
            'priority' => $this->faker->randomElement(Ticket::PRIORITIES),
            'status' => Ticket::STATUS_OPEN,
            'description' => $this->faker->paragraph(),
        ];
    }

    /**
     * Assign the ticket to a system user and mark it assigned.
     */
    public function assigned(?User $assignee = null): static
    {
        return $this->state(fn (): array => [
            'assignee_id' => $assignee?->getKey() ?? User::factory()->state(['role' => User::ROLE_CUSTOMER_CARE]),
            'status' => Ticket::STATUS_ASSIGNED,
        ]);
    }

    /**
     * Mark the ticket as in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (): array => ['status' => Ticket::STATUS_IN_PROGRESS]);
    }

    /**
     * Mark the ticket as resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (): array => ['status' => Ticket::STATUS_RESOLVED]);
    }

    /**
     * Mark the ticket as closed.
     */
    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => Ticket::STATUS_CLOSED]);
    }
}
