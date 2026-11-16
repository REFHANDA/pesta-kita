<?php

namespace Database\Factories;

use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteProposal>
 */
class QuoteProposalFactory extends Factory
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
            'proposed_amount' => fake()->numberBetween(500_000, 25_000_000),
            'breakdown' => null,
            'notes' => fake()->sentence(),
            'submitted_at' => now(),
        ];
    }
}
