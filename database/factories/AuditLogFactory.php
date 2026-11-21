<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => 'ubah_status',
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'subject_type' => 'event',
            'subject_id' => Event::factory(),
            'from_status' => 'draft',
            'to_status' => 'diajukan',
            'reason' => fake()->sentence(),
            'ip' => fake()->ipv4(),
        ];
    }

    /**
     * Aksi sistem tanpa aktor manusia (actor_id null).
     */
    public function sistem(): static
    {
        return $this->state(fn (array $attributes) => [
            'actor_id' => null,
        ]);
    }
}
