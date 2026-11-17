<?php

namespace Tests\Feature\Portal;

use App\Enums\CatalogItemStatus;
use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PricingMode;
use App\Enums\QuoteDecisionResult;
use App\Enums\QuoteRequestStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\QuoteDecision;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientEventWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_and_update_an_owned_draft_event(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $client = User::factory()->klien()->create();

        $createResponse = $this->actingAs($client)->post(route('klien.acara.store'), [
            'name' => 'Pesta Kebun Raka',
            'event_date' => '2030-02-01',
            'guest_count' => 80,
            'theme' => 'Taman',
            'description' => 'Acara keluarga.',
        ]);
        $event = Event::query()->sole();

        $createResponse->assertRedirect(route('klien.acara.detail', $event));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'client_id' => $client->id,
            'status' => EventStatus::Draft->value,
        ]);

        $this->put(route('klien.acara.update', $event), [
            'name' => 'Pesta Kebun Raka Dua',
            'event_date' => '2030-02-02',
            'guest_count' => 90,
            'theme' => 'Malam',
            'description' => 'Pembaruan draft.',
            'status' => EventStatus::Terkonfirmasi->value,
            'client_id' => User::factory()->klien()->create()->id,
        ])->assertRedirect(route('klien.acara.detail', $event));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'client_id' => $client->id,
            'name' => 'Pesta Kebun Raka Dua',
            'event_date' => '2030-02-02',
            'guest_count' => 90,
            'status' => EventStatus::Draft->value,
        ]);
    }

    public function test_client_can_add_only_a_fixed_price_public_item_using_server_price(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'catalog_price' => 2_500_000,
            'pricing_mode' => PricingMode::Tetap,
            'status' => CatalogItemStatus::Aktif,
        ]);

        $this->actingAs($client)->post(route('klien.acara.items.store', $event), [
            'subject' => 'catalog_item:'.$item->id,
            'catalog_price_snapshot' => 1,
            'final_price' => 1,
            'status' => OrderItemStatus::Mengikat->value,
        ])->assertRedirect(route('klien.acara.detail', $event));

        $this->assertDatabaseHas('order_items', [
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'catalog_price_snapshot' => 2_500_000,
            'final_price' => null,
            'status' => OrderItemStatus::MenungguAdmin->value,
        ]);
    }

    public function test_submitting_a_draft_converts_approved_linked_quotes_to_order_items(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'catalog_price' => 3_000_000,
            'pricing_mode' => PricingMode::PerluPenawaran,
            'status' => CatalogItemStatus::Aktif,
        ]);
        $quoteRequest = QuoteRequest::factory()->create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'catalog_price_snapshot' => 3_000_000,
            'status' => QuoteRequestStatus::DisetujuiAdmin,
        ]);
        QuoteDecision::factory()->for($quoteRequest)->create([
            'decision' => QuoteDecisionResult::Disetujui,
            'approved_amount' => 2_750_000,
        ]);

        $this->actingAs($client)
            ->post(route('klien.acara.submit', $event))
            ->assertRedirect(route('klien.acara.detail', $event));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => EventStatus::Diajukan->value,
        ]);
        $this->assertDatabaseHas('order_items', [
            'event_id' => $event->id,
            'quote_request_id' => $quoteRequest->id,
            'catalog_price_snapshot' => 3_000_000,
            'proposed_price_snapshot' => 2_750_000,
            'status' => OrderItemStatus::MenungguAdmin->value,
        ]);
    }

    public function test_client_cannot_change_another_clients_event(): void
    {
        $client = User::factory()->klien()->create();
        $otherEvent = Event::factory()->create();

        $this->actingAs($client)
            ->put(route('klien.acara.update', $otherEvent), [
                'name' => 'Acara asing',
                'event_date' => now()->addMonth()->toDateString(),
                'guest_count' => 30,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('events', [
            'id' => $otherEvent->id,
            'name' => $otherEvent->name,
            'status' => EventStatus::Draft->value,
        ]);
    }
}
