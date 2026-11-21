<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VenueProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueProfile>
 */
class VenueProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // State role langsung (bukan UserFactory::venue()) agar profil
            // tidak dibuat ganda lewat afterCreating.
            'user_id' => User::factory()->state(['role' => Role::Venue]),
            'business_name' => fake()->company(),
            'category' => null,
            'address' => fake()->address(),
            'pic_name' => fake()->name(),
            'service_area' => fake()->city(),
            'description' => fake()->sentence(),
            'verification_status' => VerificationStatus::Menunggu,
            'verified_at' => null,
        ];
    }

    /**
     * Profil provider masih menunggu verifikasi (T2).
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Menunggu,
            'verified_at' => null,
        ]);
    }

    /**
     * Profil provider sudah terverifikasi Admin.
     */
    public function terverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Terverifikasi,
            'verified_at' => now(),
        ]);
    }
}
