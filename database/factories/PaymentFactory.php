<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\CompanyBankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'company_bank_account_id' => CompanyBankAccount::factory(),
            'amount' => fake()->numberBetween(10, 50) * 1_000_000 + fake()->numberBetween(100, 999),
            'sender_name' => fake()->name(),
            'transfer_date' => now()->toDateString(),
            'proof_media_id' => null,
            'status' => PaymentStatus::MenungguValidasi,
            'admin_note' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }

    /**
     * Pembayaran sudah diverifikasi valid oleh Admin.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Valid,
            'confirmed_at' => now(),
        ]);
    }
}
