<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueRoom>
 */
class VenueRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            // Nama unik per venue dijaga unique (venue_id, name).
            'name' => 'Ruang '.fake()->unique()->numerify('###'),
            'capacity' => fake()->numberBetween(20, 300),
        ];
    }
}
