<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'version' => 1,
            'unique_code' => fake()->unique()->numberBetween(100, 999),
            'active_code' => fake()->unique()->numberBetween(100, 999),
            'provider_subtotal' => 100_000,
            'commission_total' => 10_000,
            'cleaning_total' => 0,
            'total_amount' => 110_123,
            'status' => 'terbit',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ];
    }
}
