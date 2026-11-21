<?php

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
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
            'type' => InvoiceItemType::HargaPenyedia,
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(1, 10) * 1_000_000,
        ];
    }

    /**
     * Baris rincian komisi platform.
     */
    public function komisi(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceItemType::Komisi,
            'description' => 'Komisi Platform (10%)',
        ]);
    }

    /**
     * Baris rincian biaya kebersihan venue.
     */
    public function kebersihan(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_item_id' => null,
            'type' => InvoiceItemType::Kebersihan,
            'description' => 'Biaya Kebersihan Venue',
            'amount' => 500_000,
        ]);
    }
}
