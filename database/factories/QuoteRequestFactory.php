<?php

namespace Database\Factories;

use App\Enums\QuoteRequestStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\QuoteDecision;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->klien(),
            // Nullable selama acara masih draft.
            'event_id' => null,
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'subject_type' => 'catalog_item',
            'subject_id' => CatalogItem::factory(),
            'event_date' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'guest_count' => fake()->numberBetween(50, 500),
            'budget' => fake()->numberBetween(5_000_000, 50_000_000),
            'notes' => fake()->sentence(),
            'catalog_price_snapshot' => fake()->numberBetween(500_000, 25_000_000),
            'status' => QuoteRequestStatus::Diajukan,
        ];
    }

    /**
     * Permintaan penawaran terkait sebuah acara.
     */
    public function denganEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => Event::factory(),
        ]);
    }

    /**
     * Permintaan penawaran sudah memiliki usulan harga penyedia.
     */
    public function denganProposal(): static
    {
        return $this->afterCreating(function (QuoteRequest $quoteRequest) {
            QuoteProposal::factory()->for($quoteRequest)->create();
            $quoteRequest->update(['status' => QuoteRequestStatus::DiusulkanPenyedia]);
        });
    }

    /**
     * Permintaan penawaran sudah memiliki keputusan final Admin.
     */
    public function denganKeputusan(): static
    {
        return $this->afterCreating(function (QuoteRequest $quoteRequest) {
            QuoteDecision::factory()->for($quoteRequest)->create();
            $quoteRequest->update(['status' => QuoteRequestStatus::DisetujuiAdmin]);
        });
    }
}
