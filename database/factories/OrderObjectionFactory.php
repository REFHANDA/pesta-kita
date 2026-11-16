<?php

namespace Database\Factories;

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
            'reason' => fake()->paragraph(),
            'status' => 'diajukan',
            'admin_decision' => null,
            'decided_at' => null,
        ];
    }
}
