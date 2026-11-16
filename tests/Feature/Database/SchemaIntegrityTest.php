<?php

namespace Tests\Feature\Database;

use App\Enums\CatalogKind;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\OrderItem;
use App\Models\QuoteDecision;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueFacility;
use App\Models\VenueRoom;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * BE-2 C.6 / instruksi §8: integritas skema — foreign key, unique,
 * cascade, nullable B4/B5, kind B15, integer rupiah, dan soft delete.
 * Berjalan pada SQLite memory dengan foreign-key enforcement aktif.
 */
class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ---- 1. Foreign key menolak orphan records ----

    public function test_vendor_profile_rejects_orphan_user(): void
    {
        $this->expectException(QueryException::class);
        VendorProfile::factory()->create(['user_id' => 999_999]);
    }

    public function test_catalog_item_rejects_orphan_vendor_profile(): void
    {
        $this->expectException(QueryException::class);
        CatalogItem::factory()->create(['vendor_profile_id' => 999_999]);
    }

    public function test_venue_room_rejects_orphan_venue(): void
    {
        $this->expectException(QueryException::class);
        VenueRoom::factory()->create(['venue_id' => 999_999]);
    }

    public function test_order_item_rejects_orphan_event(): void
    {
        $this->expectException(QueryException::class);
        OrderItem::factory()->create(['event_id' => 999_999]);
    }

    public function test_quote_proposal_rejects_orphan_quote_request(): void
    {
        $this->expectException(QueryException::class);
        QuoteProposal::factory()->create(['quote_request_id' => 999_999]);
    }

    public function test_quote_decision_rejects_orphan_quote_request(): void
    {
        $this->expectException(QueryException::class);
        QuoteDecision::factory()->create(['quote_request_id' => 999_999]);
    }

    // ---- 2. Unique availability (subject, date) ----

    public function test_availability_unique_per_subject_and_date(): void
    {
        $room = VenueRoom::factory()->create();
        AvailabilityDay::factory()->create([
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'date' => '2026-12-12',
        ]);

        $this->expectException(QueryException::class);
        AvailabilityDay::factory()->create([
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'date' => '2026-12-12',
        ]);
    }

    // ---- 3. Email tetap unik ----

    public function test_email_is_unique(): void
    {
        User::factory()->create(['email' => 'sama@pestakita.test']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'sama@pestakita.test']);
    }

    // ---- 4 & 5. Nama room/facility unik per venue ----

    public function test_room_name_unique_within_same_venue(): void
    {
        $venue = Venue::factory()->create();
        VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Ballroom']);

        $this->expectException(QueryException::class);
        VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Ballroom']);
    }

    public function test_room_name_may_repeat_across_different_venues(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();
        VenueRoom::factory()->create(['venue_id' => $venueA->id, 'name' => 'Ballroom']);
        VenueRoom::factory()->create(['venue_id' => $venueB->id, 'name' => 'Ballroom']);

        $this->assertSame(2, VenueRoom::where('name', 'Ballroom')->count());
    }

    public function test_facility_name_unique_within_same_venue(): void
    {
        $venue = Venue::factory()->create();
        VenueFacility::factory()->create(['venue_id' => $venue->id, 'name' => 'Proyektor']);

        $this->expectException(QueryException::class);
        VenueFacility::factory()->create(['venue_id' => $venue->id, 'name' => 'Proyektor']);
    }

    // ---- 6. Cascade penghapusan room/facility saat venue dihapus ----

    public function test_deleting_venue_cascades_rooms_and_facilities(): void
    {
        $venue = Venue::factory()->create();
        VenueRoom::factory()->create(['venue_id' => $venue->id]);
        VenueFacility::factory()->create(['venue_id' => $venue->id]);

        // Venue memakai SoftDeletes; forceDelete memicu cascade FK.
        $venue->forceDelete();

        $this->assertSame(0, VenueRoom::where('venue_id', $venue->id)->count());
        $this->assertSame(0, VenueFacility::where('venue_id', $venue->id)->count());
    }

    public function test_deleting_quote_request_cascades_proposals(): void
    {
        $quote = QuoteRequest::factory()->create();
        QuoteProposal::factory()->create(['quote_request_id' => $quote->id]);

        $quote->delete();

        $this->assertSame(0, QuoteProposal::where('quote_request_id', $quote->id)->count());
    }

    // ---- 7. cleaning_fee dan deposit_amount menerima null (B4/B5) ----

    public function test_cleaning_fee_and_deposit_accept_null(): void
    {
        $venue = Venue::factory()->tanpaCleaningFee()->create();

        $this->assertNull($venue->cleaning_fee);
        $this->assertNull($venue->deposit_amount);
        $this->assertIsInt($venue->rental_price);
    }

    public function test_cleaning_fee_and_deposit_accept_integer_values(): void
    {
        $venue = Venue::factory()->denganDeposit()->create();

        $this->assertIsInt($venue->cleaning_fee);
        $this->assertIsInt($venue->deposit_amount);
    }

    // ---- 8 & 9. kind menerima kedua nilai; peralatan satu tabel (B15) ----

    public function test_kind_accepts_layanan_and_peralatan_in_same_table(): void
    {
        $layanan = CatalogItem::factory()->create();
        $peralatan = CatalogItem::factory()->peralatan()->create();

        $this->assertSame(CatalogKind::Layanan, $layanan->kind);
        $this->assertSame(CatalogKind::Peralatan, $peralatan->kind);

        // Keduanya hidup di tabel catalog_items yang sama.
        $this->assertSame('catalog_items', (new CatalogItem)->getTable());
        $this->assertSame(2, CatalogItem::count());
        $this->assertSame(1, CatalogItem::where('kind', CatalogKind::Peralatan)->count());

        // Tidak ada tabel equipment terpisah.
        $this->assertFalse(Schema::hasTable('equipment'));
        $this->assertFalse(Schema::hasTable('equipment_items'));
    }

    // ---- 10. Nilai uang disimpan sebagai integer ----

    public function test_money_amounts_are_stored_as_integer(): void
    {
        $item = CatalogItem::factory()->create(['catalog_price' => 15_000_000]);
        $venue = Venue::factory()->create([
            'rental_price' => 25_000_000,
            'cleaning_fee' => 500_000,
        ]);

        $this->assertIsInt($item->catalog_price);
        $this->assertIsInt($venue->rental_price);
        $this->assertIsInt($venue->cleaning_fee);

        // Nilai mentah di database juga integer, bukan float/decimal.
        $this->assertIsInt(DB::table('catalog_items')->where('id', $item->id)->value('catalog_price'));
        $this->assertIsInt(DB::table('venues')->where('id', $venue->id)->value('rental_price'));
    }

    // ---- 11. Catalog soft-deleted tidak muncul pada query default ----

    public function test_soft_deleted_catalog_item_hidden_from_default_queries(): void
    {
        $item = CatalogItem::factory()->create();
        $item->delete();

        $this->assertNull(CatalogItem::find($item->id));
        $this->assertSame(0, CatalogItem::count());
        $this->assertNotNull(CatalogItem::withTrashed()->find($item->id));
        $this->assertSame(1, CatalogItem::withTrashed()->count());
    }

    public function test_soft_deleted_venue_hidden_from_default_queries(): void
    {
        $venue = Venue::factory()->create();
        $venue->delete();

        $this->assertNull(Venue::find($venue->id));
        $this->assertSame(0, Venue::count());
        $this->assertNotNull(Venue::withTrashed()->find($venue->id));
    }

    // ---- 14. Satu keputusan final per quote request ----

    public function test_only_one_final_decision_per_quote_request(): void
    {
        $quote = QuoteRequest::factory()->create();
        QuoteDecision::factory()->create(['quote_request_id' => $quote->id]);

        $this->expectException(QueryException::class);
        QuoteDecision::factory()->create(['quote_request_id' => $quote->id]);
    }
}
