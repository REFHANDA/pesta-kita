<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientProfile>
 */
class ClientProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // State role langsung (bukan UserFactory::klien()) agar profil
            // tidak dibuat ganda lewat afterCreating.
            'user_id' => User::factory()->state(['role' => Role::Klien]),
        ];
    }
}
