<?php

namespace Database\Factories;

use App\Enums\PriceChangeStatus;
use App\Models\CatalogItem;
use App\Models\PriceChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceChangeRequest>
 */
class PriceChangeRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldPrice = fake()->numberBetween(500_000, 25_000_000);

        return [
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'subject_type' => 'catalog_item',
            'subject_id' => CatalogItem::factory(),
            'old_price' => $oldPrice,
            'new_price' => $oldPrice + fake()->numberBetween(100_000, 5_000_000),
            'reason' => fake()->sentence(),
            'status' => PriceChangeStatus::Menunggu,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Pengajuan disetujui Admin.
     */
    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PriceChangeStatus::Disetujui,
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Pengajuan ditolak Admin.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PriceChangeStatus::Ditolak,
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }
}
