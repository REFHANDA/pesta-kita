<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Models\AvailabilityDay;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityDay>
 */
class AvailabilityDayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'subject_type' => 'venue_room',
            'subject_id' => VenueRoom::factory(),
            'date' => fake()->unique()->dateTimeBetween('+1 day', '+60 days')->format('Y-m-d'),
            'status' => AvailabilityStatus::Tersedia,
        ];
    }

    /**
     * Tanggal ditandai tidak tersedia oleh penyedia (B11).
     */
    public function tidakTersedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AvailabilityStatus::TidakTersedia,
        ]);
    }

    /**
     * Tanggal belum dikonfirmasi penyedia (B11).
     */
    public function belumDikonfirmasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AvailabilityStatus::BelumDikonfirmasi,
        ]);
    }
}
