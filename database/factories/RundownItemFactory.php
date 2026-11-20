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
            'created_by' => User::factory()->klien(),
            'order_item_id' => null,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'activity' => fake()->sentence(3),
            'description' => fake()->sentence(),
        ];
    }
}
