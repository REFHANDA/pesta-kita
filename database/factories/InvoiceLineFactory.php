<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
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
            'order_item_id' => OrderItem::factory(),
            'description_snapshot' => fake()->words(3, true),
            'provider_amount' => 100_000,
            'commission_amount' => 10_000,
            'cleaning_fee_amount' => 0,
            'total_amount' => 110_000,
        ];
    }
}
