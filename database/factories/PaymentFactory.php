<?php

namespace Database\Factories;

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
            'active_invoice_id' => null,
            'company_bank_account_id' => CompanyBankAccount::factory(),
            'bank_name_snapshot' => 'BCA',
            'account_number_snapshot' => '1234567890',
            'account_name_snapshot' => 'PT Pesta Kita',
            'sender_name' => fake()->name(),
            'amount' => 110_123,
            'transferred_at' => now(),
            'proof_disk' => 'private',
            'proof_path' => 'payment-proofs/factory-proof.pdf',
            'status' => 'ditolak',
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => 'Bukti perlu diperbaiki.',
        ];
    }
}
