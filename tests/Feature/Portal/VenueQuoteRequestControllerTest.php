<?php

namespace Tests\Feature\Portal;

use App\Enums\QuoteRequestStatus;
use App\Enums\VerificationStatus;
use App\Models\Event;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueQuoteRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_before_submitting_a_proposal(): void
    {
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $quoteRequest = $this->quoteForRoom($room);

        $this->post(route('venue.penawaran.store', $quoteRequest), [])
            ->assertRedirect('/login');
        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_venue_owner_proposal_updates_request_and_ignores_forged_fields(): void
    {
        $this->travelTo('2030-01-01 10:00:00');
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $quoteRequest = $this->quoteForRoom($room);

        $response = $this->actingAs($owner)
            ->from(route('venue.penawaran.detail', $quoteRequest))
            ->post(route('venue.penawaran.store', $quoteRequest), [
                'proposed_amount' => 15_000_000,
                'notes' => 'Termasuk penggunaan ruang persiapan.',
                'quote_request_id' => 999,
                'breakdown' => ['total' => 1],
                'submitted_at' => '2020-01-01',
            ]);

        $response->assertRedirect(route('venue.penawaran.detail', $quoteRequest))
            ->assertSessionHas('status');
        $this->assertDatabaseHas('quote_proposals', [
            'quote_request_id' => $quoteRequest->id,
            'proposed_amount' => 15_000_000,
            'breakdown' => null,
            'notes' => 'Termasuk penggunaan ruang persiapan.',
        ]);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);
    }

    public function test_venue_owner_cannot_propose_for_another_owners_room(): void
    {
        $owner = User::factory()->venue()->create();
        $otherOwner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($otherOwner);
        $quoteRequest = $this->quoteForRoom($room);

        $this->actingAs($owner)
            ->post(route('venue.penawaran.store', $quoteRequest), ['proposed_amount' => 15_000_000])
            ->assertNotFound();

        $this->assertDatabaseCount('quote_proposals', 0);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::Diajukan->value,
        ]);
    }

    public function test_venue_owner_cannot_submit_a_zero_value_proposal(): void
    {
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $quoteRequest = $this->quoteForRoom($room);

        $response = $this->actingAs($owner)->post(route('venue.penawaran.store', $quoteRequest), [
            'proposed_amount' => 0,
        ]);

        $response->assertSessionHasErrors('proposed_amount');
        $this->assertSame('Harga usulan minimal Rp 1.', session('errors')->first('proposed_amount'));
        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_venue_owner_cannot_submit_a_second_proposal_after_request_status_changes(): void
    {
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $quoteRequest = $this->quoteForRoom($room);
        $url = route('venue.penawaran.store', $quoteRequest);

        $this->actingAs($owner)->post($url, ['proposed_amount' => 15_000_000])
            ->assertRedirect(route('venue.penawaran.detail', $quoteRequest));
        $response = $this->actingAs($owner)
            ->from(route('venue.penawaran.detail', $quoteRequest))
            ->post($url, ['proposed_amount' => 14_000_000]);

        $response->assertSessionHasErrors('quote_request');
        $this->assertDatabaseCount('quote_proposals', 1);
        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'status' => QuoteRequestStatus::DiusulkanPenyedia->value,
        ]);
    }

    public function test_non_venue_role_cannot_submit_a_proposal(): void
    {
        $client = User::factory()->klien()->create();
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $quoteRequest = $this->quoteForRoom($room);

        $this->actingAs($client)
            ->post(route('venue.penawaran.store', $quoteRequest), ['proposed_amount' => 15_000_000])
            ->assertForbidden();

        $this->assertDatabaseCount('quote_proposals', 0);
    }

    public function test_venue_detail_does_not_expose_client_or_event_names(): void
    {
        $owner = User::factory()->venue()->create();
        $room = $this->publicRoomFor($owner);
        $client = User::factory()->klien()->create(['name' => 'IDENTITAS KLIEN RAHASIA']);
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'NAMA ACARA RAHASIA',
        ]);
        $quoteRequest = QuoteRequest::create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'event_date' => '2030-02-01',
            'catalog_price_snapshot' => 15_000_000,
            'status' => QuoteRequestStatus::Diajukan,
        ]);

        $this->actingAs($owner)
            ->get(route('venue.penawaran.detail', $quoteRequest))
            ->assertOk()
            ->assertSee($room->name)
            ->assertDontSee('IDENTITAS KLIEN RAHASIA')
            ->assertDontSee('NAMA ACARA RAHASIA');
    }

    private function publicRoomFor(User $owner): VenueRoom
    {
        $owner->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $venue = Venue::factory()->create([
            'venue_profile_id' => $owner->venueProfile->id,
            'verification_status' => VerificationStatus::Terverifikasi,
            'rental_price' => 15_000_000,
        ]);

        return VenueRoom::factory()->create(['venue_id' => $venue->id]);
    }

    private function quoteForRoom(VenueRoom $room): QuoteRequest
    {
        $client = User::factory()->klien()->create();

        return QuoteRequest::create([
            'client_id' => $client->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'event_date' => now()->addDays(30)->toDateString(),
            'catalog_price_snapshot' => 15_000_000,
            'status' => QuoteRequestStatus::Diajukan,
        ]);
    }
}
