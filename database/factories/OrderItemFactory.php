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
            'quantity' => 1,
            'usage_date' => null,
            'catalog_price_snapshot' => fake()->numberBetween(500_000, 25_000_000),
            'provider_price' => null,
            'commission' => 0,
            'status' => OrderItemStatus::MenungguReview,
            'rejection_reason' => null,
            'admin_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_due_at' => null,
            'approved_at' => null,
            'objection_deadline' => null,
            'replaced_by_order_item_id' => null,
            'hold_key' => null,
        ];
    }

    /**
     * Item berstatus draft (ditambahkan ke acara draft sebelum diajukan).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderItemStatus::Draft,
            'provider_price' => null,
            'commission' => 0,
            'hold_key' => null,
        ]);
    }

    /**
     * Item disetujui Admin dengan harga penyedia terkunci dan komisi 10%.
     */
    public function disetujui(): static
    {
        return $this->state(function (array $attributes) {
            $price = fake()->numberBetween(500_000, 25_000_000);

            return [
                'status' => OrderItemStatus::Disetujui,
                'provider_price' => $price,
                'commission' => (int) round($price * 0.10),
                'approved_at' => now(),
            ];
        });
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
