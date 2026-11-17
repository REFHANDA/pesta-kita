<?php

namespace Tests\Feature\Portal;

use App\Enums\QuoteRequestStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientQuoteRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_submitting_a_request(): void
    {
        $this->post(route('klien.penawaran.store'), [])->assertRedirect('/login');
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_valid_client_request_saves_public_item_snapshot_and_ignores_forged_fields(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $client = User::factory()->klien()->create();
        $spoofedClient = User::factory()->klien()->create();
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->perluPenawaran()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'catalog_price' => 8_250_000,
        ]);
        $event = Event::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'catalog_item:'.$item->id,
            'event_id' => $event->id,
            'event_date' => '2030-02-01',
            'guest_count' => 80,
            'budget' => 10_000_000,
            'notes' => 'Perlu akses untuk kursi roda.',
            'client_id' => $spoofedClient->id,
            'subject_type' => 'venue_room',
            'subject_id' => 999,
            'catalog_price_snapshot' => 1,
            'status' => QuoteRequestStatus::DisetujuiAdmin->value,
        ]);

        $quoteRequest = QuoteRequest::query()->sole();

        $response->assertRedirect(route('klien.penawaran.detail', $quoteRequest))
            ->assertSessionHas('status');
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'event_date' => '2030-02-01',
            'catalog_price_snapshot' => 8_250_000,
            'status' => QuoteRequestStatus::Diajukan->value,
        ]);
        $this->assertSame(80, $quoteRequest->guest_count);
        $this->assertSame('Perlu akses untuk kursi roda.', $quoteRequest->notes);
    }

    public function test_client_cannot_attach_another_clients_draft_event(): void
    {
        $client = User::factory()->klien()->create();
        $otherClient = User::factory()->klien()->create();
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->perluPenawaran()->create([
            'vendor_profile_id' => $vendorProfile->id,
        ]);
        $foreignEvent = Event::factory()->create(['client_id' => $otherClient->id]);

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'catalog_item:'.$item->id,
            'event_id' => $foreignEvent->id,
            'event_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHasErrors('event_id');
        $this->assertSame('Acara harus berupa draft milik akun Anda.', session('errors')->first('event_id'));
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_client_cannot_attach_an_event_that_is_not_a_draft(): void
    {
        $client = User::factory()->klien()->create();
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->perluPenawaran()->create([
            'vendor_profile_id' => $vendorProfile->id,
        ]);
        $event = Event::factory()->diajukan()->create(['client_id' => $client->id]);

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'catalog_item:'.$item->id,
            'event_id' => $event->id,
            'event_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHasErrors('event_id');
        $this->assertSame('Acara harus berupa draft milik akun Anda.', session('errors')->first('event_id'));
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_client_cannot_request_a_date_in_the_past(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $client = User::factory()->klien()->create();
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->perluPenawaran()->create([
            'vendor_profile_id' => $vendorProfile->id,
        ]);

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'catalog_item:'.$item->id,
            'event_date' => '2029-12-31',
        ]);

        $response->assertSessionHasErrors('event_date');
        $this->assertSame('Tanggal acara tidak boleh di masa lalu.', session('errors')->first('event_date'));
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_valid_venue_request_snapshots_the_venue_rental_price(): void
    {
        $client = User::factory()->klien()->create();
        $venueProfile = VenueProfile::factory()->terverifikasi()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
            'rental_price' => 17_500_000,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'venue_room:'.$room->id,
            'event_date' => now()->addDays(10)->toDateString(),
        ]);

        $quoteRequest = QuoteRequest::query()->sole();
        $response->assertRedirect(route('klien.penawaran.detail', $quoteRequest));
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'catalog_price_snapshot' => 17_500_000,
            'status' => QuoteRequestStatus::Diajukan->value,
        ]);
    }

    public function test_catalog_details_link_to_prefilled_quote_requests(): void
    {
        $client = User::factory()->klien()->create();
        $vendorProfile = VendorProfile::factory()->terverifikasi()->create();
        $item = CatalogItem::factory()->perluPenawaran()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Layanan Perlu Penawaran',
        ]);
        $venueProfile = VenueProfile::factory()->terverifikasi()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($client)
            ->get(route('klien.katalog.vendor.detail', $item))
            ->assertOk()
            ->assertSee(route('klien.penawaran', ['subject' => 'catalog_item:'.$item->id]));
        $this->actingAs($client)
            ->get(route('klien.katalog.venue.detail', $venue))
            ->assertOk()
            ->assertSee(route('klien.penawaran', ['subject' => 'venue_room:'.$room->id]));
    }

    public function test_client_cannot_request_a_hidden_catalog_item(): void
    {
        $client = User::factory()->klien()->create();
        $unverifiedItem = CatalogItem::factory()->perluPenawaran()->create();

        $response = $this->actingAs($client)->post(route('klien.penawaran.store'), [
            'subject' => 'catalog_item:'.$unverifiedItem->id,
            'event_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertSessionHasErrors('subject');
        $this->assertSame(
            'Layanan atau ruangan venue sudah tidak tersedia untuk permintaan penawaran.',
            session('errors')->first('subject'),
        );
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_non_client_cannot_submit_a_quote_request(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post(route('klien.penawaran.store'), [])->assertForbidden();
        $this->assertDatabaseCount('quote_requests', 0);
    }
}
