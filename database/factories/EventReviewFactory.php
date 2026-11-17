<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReview>
 */
class EventReviewFactory extends Factory
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
            'order_item_id' => OrderItem::factory(),
            'client_id' => User::factory()->klien(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
