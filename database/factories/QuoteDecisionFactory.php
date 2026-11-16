<?php

namespace Database\Factories;

use App\Enums\QuoteDecisionResult;
use App\Models\QuoteDecision;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteDecision>
 */
class QuoteDecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_request_id' => QuoteRequest::factory(),
            'admin_id' => User::factory()->admin(),
            'decision' => QuoteDecisionResult::Disetujui,
            'approved_amount' => fake()->numberBetween(500_000, 25_000_000),
            'reason' => null,
            'decided_at' => now(),
        ];
    }

    /**
     * Keputusan menolak; approved_amount kosong dan alasan terisi.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => QuoteDecisionResult::Ditolak,
            'approved_amount' => null,
            'reason' => fake()->sentence(),
        ]);
    }
}
