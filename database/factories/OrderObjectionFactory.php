<?php

namespace Database\Factories;

use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use App\Enums\Role;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderObjection>
 */
class OrderObjectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            // State role langsung agar tidak membuat profil ganda.
            'provider_user_id' => User::factory()->state(['role' => Role::Vendor]),
            'category' => fake()->randomElement(OrderObjectionCategory::cases()),
            'reason' => fake()->paragraph(),
            'evidence_media_id' => null,
            'is_emergency' => false,
            'status' => OrderObjectionStatus::Diajukan,
            'decision_reason' => null,
            'reviewed_by' => null,
            'admin_decision' => null,
            'decided_at' => null,
        ];
    }
}
