<?php

namespace Tests\Feature\Portal;

use App\Enums\QuoteRequestStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorQuoteRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_submitting_a_proposal(): void
    {
        $vendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($vendor);

        $this->post(route('vendor.penawaran.store', $quoteRequest), [])
            ->assertRedirect('/login');
        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_vendor_proposal_updates_request_and_ignores_client_controlled_fields(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $vendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($vendor);
        $notes = '<img src=x onerror=alert(1)>';

        $response = $this->actingAs($vendor)
            ->from(route('vendor.penawaran.detail', $quoteRequest))
            ->post(route('vendor.penawaran.store', $quoteRequest), [
                'proposed_amount' => 7_900_000,
                'notes' => $notes,
                'quote_request_id' => 999,
                'breakdown' => ['total' => 1],
                'submitted_at' => '2020-01-01',
            ]);

        $response->assertRedirect(route('vendor.penawaran.detail', $quoteRequest))
            ->assertSessionHas('status');
        $this->assertDatabaseHas('quote_proposals', [
            'quote_request_id' => $quoteRequest->id,
            'proposed_amount' => 7_900_000,
            'breakdown' => null,
            'notes' => $notes,
        ]);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);

        $client = User::query()->findOrFail($quoteRequest->client_id);
        $clientResponse = $this->actingAs($client)
            ->get(route('klien.penawaran.detail', $quoteRequest));
        $clientResponse->assertOk()->assertSee($notes);
        $clientResponse->assertDontSee($notes, false);
    }

    public function test_vendor_cannot_propose_for_another_vendors_item(): void
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($otherVendor);

        $this->actingAs($vendor)
            ->post(route('vendor.penawaran.store', $quoteRequest), ['proposed_amount' => 5_000_000])
            ->assertNotFound();

        $this->assertDatabaseCount('quote_proposals', 0);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::Diajukan->value,
        ]);
    }

    public function test_vendor_cannot_submit_a_zero_value_proposal(): void
    {
        $vendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($vendor);

        $response = $this->actingAs($vendor)->post(route('vendor.penawaran.store', $quoteRequest), [
            'proposed_amount' => 0,
        ]);

        $response->assertSessionHasErrors('proposed_amount');
        $this->assertSame('Harga usulan minimal Rp 1.', session('errors')->first('proposed_amount'));
        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_vendor_cannot_submit_a_second_proposal_after_request_status_changes(): void
    {
        $vendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($vendor);
        $url = route('vendor.penawaran.store', $quoteRequest);

        $this->actingAs($vendor)->post($url, ['proposed_amount' => 5_000_000])
            ->assertRedirect(route('vendor.penawaran.detail', $quoteRequest));
        $response = $this->actingAs($vendor)
            ->from(route('vendor.penawaran.detail', $quoteRequest))
            ->post($url, ['proposed_amount' => 4_000_000]);

        $response->assertSessionHasErrors('quote_request');
        $this->assertDatabaseCount('quote_proposals', 1);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);
    }

    public function test_non_vendor_cannot_submit_a_proposal(): void
    {
        $client = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();
        $quoteRequest = $this->quoteForVendor($vendor);

        $this->actingAs($client)
            ->post(route('vendor.penawaran.store', $quoteRequest), ['proposed_amount' => 5_000_000])
            ->assertForbidden();

        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_vendor_detail_does_not_expose_client_or_event_names(): void
    {
        $vendor = User::factory()->vendor()->create();
        $client = User::factory()->klien()->create(['name' => 'IDENTITAS KLIEN RAHASIA']);
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'NAMA ACARA RAHASIA',
        ]);
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Paket Vendor Privat',
        ]);
        $quoteRequest = QuoteRequest::factory()->create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
        ]);

        $this->actingAs($vendor)
            ->get(route('vendor.penawaran.detail', $quoteRequest))
            ->assertOk()
            ->assertSee('Paket Vendor Privat')
            ->assertDontSee('IDENTITAS KLIEN RAHASIA')
            ->assertDontSee('NAMA ACARA RAHASIA');
    }

    private function quoteForVendor(User $vendor): QuoteRequest
    {
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
        ]);
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);

        return QuoteRequest::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'event_date' => now()->addDays(30)->toDateString(),
            'catalog_price_snapshot' => $item->catalog_price,
            'notes' => 'Permintaan uji.',
            'status' => QuoteRequestStatus::Diajukan,
        ]);
    }
}
