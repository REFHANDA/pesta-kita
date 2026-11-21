<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_profile_id' => VenueProfile::factory(),
            'name' => fake()->company(),
            'kind' => 'Ballroom',
            'capacity' => fake()->numberBetween(100, 1000),
            'address' => fake()->address(),
            'description' => fake()->paragraph(),
            'rules' => fake()->sentence(),
            'cancellation_policy' => fake()->sentence(),
            'rental_price' => fake()->numberBetween(10_000_000, 100_000_000),
            // B4: cleaning fee opsional, terpisah dari harga sewa.
            'cleaning_fee' => 500_000,
            'duration_hours' => 8,
            // B5: deposit hanya kolom nullable, tanpa logika pembayaran.
            'deposit_amount' => null,
            'verification_status' => VerificationStatus::Menunggu,
            'verified_at' => null,
        ];
    }

    /**
     * Venue sudah terverifikasi Admin.
     */
    public function terverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);
    }

    /**
     * Venue tanpa biaya kebersihan (B4: null diperbolehkan).
     */
    public function tanpaCleaningFee(): static
    {
        return $this->state(fn (array $attributes) => [
            'cleaning_fee' => null,
        ]);
    }

    /**
     * Venue dengan deposit (B5: nilai opsional, tanpa logika).
     */
    public function denganDeposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'deposit_amount' => 5_000_000,
        ]);
    }
}
