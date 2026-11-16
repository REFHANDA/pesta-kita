<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\VendorProfile;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'provider_type' => 'vendor_profile',
            'provider_id' => VendorProfile::factory(),
            'subject_type' => 'catalog_item',
            'subject_id' => CatalogItem::factory(),
            'quote_request_id' => null,
            'catalog_price_snapshot' => fake()->numberBetween(500_000, 25_000_000),
            'proposed_price_snapshot' => null,
            'final_price' => null,
            'status' => OrderItemStatus::MenungguAdmin,
            'rejection_reason' => null,
            // B7: hanya kolom nullable, tanpa logika kedaluwarsa.
            'objection_deadline' => null,
        ];
    }

    /**
     * Item disetujui Admin dengan harga final terkunci.
     */
    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItemStatus::Disetujui,
            'final_price' => fake()->numberBetween(500_000, 25_000_000),
        ]);
    }

    /**
     * Item ditolak Admin dengan alasan.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItemStatus::Ditolak,
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Item menunjuk ruangan venue (B6: beberapa venue per acara mungkin).
     */
    public function untukVenueRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_type' => 'venue_profile',
            'provider_id' => VenueProfile::factory(),
            'subject_type' => 'venue_room',
            'subject_id' => VenueRoom::factory(),
        ]);
    }
}
