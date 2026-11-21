<?php

namespace Tests\Feature\Database;

use App\Models\AuditLog;
use App\Models\AvailabilityDay;
use App\Models\Cancellation;
use App\Models\CatalogItem;
use App\Models\CompanyBankAccount;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\EvaluationItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Payment;
use App\Models\PriceChangeRequest;
use App\Models\RundownItem;
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
 * dengan alias enforceMorphMap, serta relasi event dan order item.
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

    public function test_morph_aliases_are_stored_not_class_names(): void
    {
        $media = Media::factory()->create();
        $this->assertSame('catalog_item', $media->owner_type);

        $item = OrderItem::factory()->create();
        $this->assertSame('vendor_profile', $item->provider_type);
        $this->assertSame('catalog_item', $item->subject_type);
    }

    public function test_event_has_order_items(): void
    {
        $event = Event::factory()->create();
        OrderItem::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(User::class, $event->client);
        $this->assertCount(1, $event->orderItems);
    }

    public function test_order_item_has_objections(): void
    {
        $item = OrderItem::factory()->create();
        OrderObjection::factory()->create(['order_item_id' => $item->id]);

        $this->assertCount(1, $item->objections);
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

    public function test_invoice_has_items_and_event(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        InvoiceItem::factory()->kebersihan()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Event::class, $invoice->event);
        $this->assertCount(2, $invoice->items);
    }

    public function test_payment_belongs_to_invoice_and_bank_account(): void
    {
        $payment = Payment::factory()->create();

        $this->assertInstanceOf(Invoice::class, $payment->invoice);
        $this->assertInstanceOf(CompanyBankAccount::class, $payment->companyBankAccount);
    }

    public function test_cancellation_belongs_to_event_and_invoice(): void
    {
        $cancellation = Cancellation::factory()->create();

        $this->assertInstanceOf(Event::class, $cancellation->event);
        $this->assertInstanceOf(Invoice::class, $cancellation->invoice);
    }

    public function test_disbursement_belongs_to_order_item_and_provider_morph(): void
    {
        $vendor = VendorProfile::factory()->create();
        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->id,
        ]);

        $this->assertInstanceOf(OrderItem::class, $disbursement->orderItem);
        $this->assertInstanceOf(VendorProfile::class, $disbursement->provider);
    }

    public function test_rundown_item_belongs_to_event_and_creator(): void
    {
        $item = RundownItem::factory()->create();

        $this->assertInstanceOf(Event::class, $item->event);
        $this->assertInstanceOf(User::class, $item->creator);
    }

    public function test_evaluation_has_items_and_belongs_to_event(): void
    {
        $eval = Evaluation::factory()->create();
        EvaluationItem::factory()->create(['evaluation_id' => $eval->id]);

        $this->assertInstanceOf(Event::class, $eval->event);
        $this->assertCount(1, $eval->items);
    }
}
