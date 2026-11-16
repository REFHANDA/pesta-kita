<?php

namespace Database\Factories;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\PricingMode;
use App\Models\CatalogItem;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_profile_id' => VendorProfile::factory(),
            'kind' => CatalogKind::Layanan,
            'name' => fake()->words(3, true),
            'category' => 'Katering & F&B',
            'description' => fake()->paragraph(),
            'catalog_price' => fake()->numberBetween(500_000, 25_000_000),
            'pricing_mode' => PricingMode::Tetap,
            'max_per_day' => null,
            'status' => CatalogItemStatus::Aktif,
            'policies' => fake()->sentence(),
        ];
    }

    /**
     * Item peralatan (B15: tetap satu tabel catalog_items).
     */
    public function peralatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => CatalogKind::Peralatan,
            'category' => 'Peralatan Pesta',
            'max_per_day' => fake()->numberBetween(10, 200),
        ]);
    }

    /**
     * Harga perlu penawaran, bukan harga tetap.
     */
    public function perluPenawaran(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_mode' => PricingMode::PerluPenawaran,
        ]);
    }

    /**
     * Item menunggu persetujuan Admin.
     */
    public function menungguPersetujuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CatalogItemStatus::MenungguPersetujuan,
        ]);
    }

    /**
     * Item dinonaktifkan penyedia.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CatalogItemStatus::Nonaktif,
        ]);
    }
}
