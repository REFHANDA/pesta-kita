<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Invoice;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefundRequest>
 */
class RefundRequestFactory extends Factory
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
            'invoice_id' => Invoice::factory(),
            'client_id' => User::factory()->klien(),
            'reason' => fake()->sentence(),
            'status' => 'diajukan',
            'submitted_at' => now(),
        ];
    }
}
