<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationItem>
 */
class EvaluationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'target_type' => fake()->randomElement(['venue', 'vendor', 'admin']),
            'target_id' => fake()->numberBetween(1, 100),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
