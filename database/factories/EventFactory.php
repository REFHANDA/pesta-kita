<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->klien(),
            'name' => fake()->words(3, true),
            'event_date' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'guest_count' => fake()->numberBetween(50, 500),
            'theme' => fake()->word(),
            'description' => fake()->sentence(),
            'status' => EventStatus::Draft,
        ];
    }

    /**
     * Acara sudah diajukan Klien.
     */
    public function diajukan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Diajukan,
        ]);
    }
}
