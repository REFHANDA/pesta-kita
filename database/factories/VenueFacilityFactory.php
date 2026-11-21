<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenueFacility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueFacility>
 */
class VenueFacilityFactory extends Factory
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
            'name' => fake()->unique()->numerify('Fasilitas ###'),
        ];
    }
}
