<?php

namespace Tests\Feature\Database;

use App\Enums\QuoteRequestStatus;
use App\Models\AuditLog;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\PriceChangeRequest;
use App\Models\QuoteDecision;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueFacility;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * BE-2 C.6 / instruksi §8: relasi domain — polymorphic morph resolves
 * dengan alias enforceMorphMap, serta rantai quote/event/order item.
 */
class DomainRelationsTest extends TestCase
{
    use RefreshDatabase;

    // ---- 12. Polymorphic relations menyelesaikan relasi dengan benar ----

    public function test_media_owner_morph_resolves(): void
    {
        $media = Media::factory()->create();
        $this->assertInstanceOf(CatalogItem::class, $media->owner);

        $venue = Venue::factory()->create();
        $mediaVenue = Media::factory()->create(['owner_type' => 'venue', 'owner_id' => $venue->id]);
        $this->assertInstanceOf(Venue::class, $mediaVenue->owner);
    }

    public function test_price_change_request_subject_morph_resolves(): void
    {
        $request = PriceChangeRequest::factory()->create();
        $this->assertInstanceOf(CatalogItem::class, $request->subject);

        $venue = Venue::factory()->create();
        $requestVenue = PriceChangeRequest::factory()->create(['subject_type' => 'venue', 'subject_id' => $venue->id]);
        $this->assertInstanceOf(Venue::class, $requestVenue->subject);
    }

    public function test_availability_day_subject_morph_resolves(): void
    {
        $day = AvailabilityDay::factory()->create();
        $this->assertInstanceOf(VenueRoom::class, $day->subject);

        $item = CatalogItem::factory()->create();
        $dayItem = AvailabilityDay::factory()->create(['subject_type' => 'catalog_item', 'subject_id' => $item->id]);
        $this->assertInstanceOf(CatalogItem::class, $dayItem->subject);
    }

    public function test_order_item_provider_and_subject_morph_resolve(): void
    {
        $item = OrderItem::factory()->create();
        $this->assertInstanceOf(VendorProfile::class, $item->provider);
        $this->assertInstanceOf(CatalogItem::class, $item->subject);

        $venueItem = OrderItem::factory()->untukVenueRoom()->create();
        $this->assertInstanceOf(VenueProfile::class, $venueItem->provider);
        $this->assertInstanceOf(VenueRoom::class, $venueItem->subject);
    }

    public function test_audit_log_subject_morph_resolves(): void
    {
        $log = AuditLog::factory()->create();
        $this->assertInstanceOf(Event::class, $log->subject);
    }

    public function test_quote_request_subject_morph_resolves(): void
    {
        $quote = QuoteRequest::factory()->create();
        $this->assertInstanceOf(CatalogItem::class, $quote->subject);
    }

    public function test_morph_aliases_are_stored_not_class_names(): void
    {
        $media = Media::factory()->create();
        $this->assertSame('catalog_item', $media->owner_type);

        $item = OrderItem::factory()->create();
        $this->assertSame('vendor_profile', $item->provider_type);
        $this->assertSame('catalog_item', $item->subject_type);
    }

    // ---- 13. Relasi quote, event, dan order item valid ----

    public function test_quote_request_without_proposal_or_decision(): void
    {
        $quote = QuoteRequest::factory()->create();

        $this->assertCount(0, $quote->proposals);
        $this->assertNull($quote->decision);
    }

    public function test_quote_request_with_proposal(): void
    {
        $quote = QuoteRequest::factory()->denganProposal()->create();

        $this->assertCount(1, $quote->proposals);
        $this->assertInstanceOf(QuoteProposal::class, $quote->proposals->first());
        $this->assertSame(QuoteRequestStatus::DiusulkanPenyedia, $quote->fresh()->status);
    }

    public function test_quote_request_with_decision(): void
    {
        $quote = QuoteRequest::factory()->denganKeputusan()->create();

        $this->assertInstanceOf(QuoteDecision::class, $quote->decision);
        $this->assertSame(QuoteRequestStatus::DisetujuiAdmin, $quote->fresh()->status);
    }

    public function test_event_has_quote_requests_and_order_items(): void
    {
        $event = Event::factory()->create();
        QuoteRequest::factory()->create(['event_id' => $event->id]);
        OrderItem::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(User::class, $event->client);
        $this->assertCount(1, $event->quoteRequests);
        $this->assertCount(1, $event->orderItems);
    }

    public function test_order_item_has_objections_and_optional_quote_request(): void
    {
        $quote = QuoteRequest::factory()->create();
        $item = OrderItem::factory()->create(['quote_request_id' => $quote->id]);
        OrderObjection::factory()->create(['order_item_id' => $item->id]);

        $this->assertCount(1, $item->objections);
        $this->assertInstanceOf(QuoteRequest::class, $item->quoteRequest);
    }

    public function test_event_can_reference_multiple_venues_via_order_items(): void
    {
        // B6: tanpa events.venue_id; beberapa venue lewat order_items.
        $this->assertFalse(Schema::hasColumn('events', 'venue_id'));

        $event = Event::factory()->create();
        OrderItem::factory()->untukVenueRoom()->create(['event_id' => $event->id]);
        OrderItem::factory()->untukVenueRoom()->create(['event_id' => $event->id]);

        $this->assertCount(2, $event->orderItems);
    }

    public function test_vendor_profile_has_catalog_items(): void
    {
        $vendor = VendorProfile::factory()->create();
        CatalogItem::factory()->create(['vendor_profile_id' => $vendor->id]);

        $this->assertCount(1, $vendor->catalogItems);
    }

    public function test_venue_profile_has_venues_with_rooms_and_facilities(): void
    {
        $profile = VenueProfile::factory()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $profile->id]);
        VenueRoom::factory()->create(['venue_id' => $venue->id]);
        VenueFacility::factory()->create(['venue_id' => $venue->id]);

        $this->assertCount(1, $profile->venues);
        $this->assertCount(1, $venue->rooms);
        $this->assertCount(1, $venue->facilities);
    }
}
