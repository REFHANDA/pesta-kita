<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\RundownItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RundownItem>
 */
class RundownItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'order_item_id' => null,
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'starts_at' => fake()->time('H:i'),
            'duration_minutes' => 30,
            'notes' => fake()->sentence(),
        ];
    }
}
