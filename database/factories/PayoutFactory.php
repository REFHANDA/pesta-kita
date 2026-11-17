<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\ProviderBankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
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
            'provider_bank_account_id' => ProviderBankAccount::factory(),
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => fake()->company(),
            'amount' => 100_000,
            'transferred_at' => now()->toDateString(),
            'proof_disk' => 'private',
            'proof_path' => null,
            'recorded_by' => User::factory()->admin(),
            'notes' => null,
        ];
    }
}
