<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
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
        $provider = fake()->numberBetween(5, 50) * 1_000_000;
        $commission = (int) round($provider * 0.10);
        $cleaning = 500_000;
        $grandTotal = $provider + $commission + $cleaning;
        $uniqueCode = fake()->numberBetween(100, 999);

        return [
            'event_id' => Event::factory(),
            'invoice_number' => 'PK/'.date('Y/m/').fake()->unique()->numerify('####'),
            'version' => 1,
            'subtotal_provider' => $provider,
            'total_commission' => $commission,
            'cleaning_fee' => $cleaning,
            'grand_total' => $grandTotal,
            'unique_code' => $uniqueCode,
            'transfer_total' => $grandTotal + $uniqueCode,
            'due_at' => now()->addHours(48),
            'status' => InvoiceStatus::BelumDibayar,
            'issued_at' => now(),
            'paid_at' => null,
        ];
    }

    /**
     * Tagihan berstatus lunas.
     */
    public function lunas(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Lunas,
            'paid_at' => now(),
        ]);
    }
}
