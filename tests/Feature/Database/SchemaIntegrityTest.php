<?php

namespace Tests\Feature\Database;

use App\Enums\CatalogKind;
use App\Enums\DisbursementType;
use App\Models\AvailabilityDay;
use App\Models\Cancellation;
use App\Models\CatalogItem;
use App\Models\Disbursement;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
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

    // ---- 12. Unique hold_key pada order_items untuk menahan tanggal venue ----

    public function test_order_item_hold_key_is_unique_when_not_null(): void
    {
        $item1 = OrderItem::factory()->create(['hold_key' => 'venue:1:2026-12-25']);
        $this->assertNotNull($item1->hold_key);

        $this->expectException(QueryException::class);
        OrderItem::factory()->create(['hold_key' => 'venue:1:2026-12-25']);
    }

    public function test_order_item_hold_key_allows_multiple_nulls(): void
    {
        $item1 = OrderItem::factory()->create(['hold_key' => null]);
        $item2 = OrderItem::factory()->create(['hold_key' => null]);

        $this->assertNull($item1->hold_key);
        $this->assertNull($item2->hold_key);
    }

    // ---- 13. Unique disbursement per order_item dan type ----

    public function test_disbursement_is_unique_per_order_item_and_type(): void
    {
        $orderItem = OrderItem::factory()->create();
        Disbursement::factory()->create([
            'order_item_id' => $orderItem->id,
            'type' => DisbursementType::HakPesanan,
        ]);

        $this->expectException(QueryException::class);
        Disbursement::factory()->create([
            'order_item_id' => $orderItem->id,
            'type' => DisbursementType::HakPesanan,
        ]);
    }

    // ---- 14. Unique evaluation per event ----

    public function test_cancellation_refund_account_columns_are_nullable(): void
    {
        $this->assertTrue(Schema::hasColumns('cancellations', ['refund_bank_name', 'refund_account_number', 'refund_account_name', 'refund_paid_at']));

        $cancellation = Cancellation::factory()->create([
            'refund_bank_name' => null, 'refund_account_number' => null, 'refund_account_name' => null, 'refund_paid_at' => null,
        ]);
        $this->assertNull($cancellation->fresh()->refund_bank_name);

        $cancellation->update(['refund_paid_at' => '2026-12-01 10:00:00', 'refund_bank_name' => 'BNI']);
        $this->assertSame('BNI', $cancellation->fresh()->refund_bank_name);
        $this->assertSame('2026-12-01 10:00:00', $cancellation->fresh()->refund_paid_at->format('Y-m-d H:i:s'));
    }

    public function test_evaluation_is_unique_per_event(): void
    {
        $event = Event::factory()->create();
        Evaluation::factory()->create(['event_id' => $event->id]);

        $this->expectException(QueryException::class);
        Evaluation::factory()->create(['event_id' => $event->id]);
    }

    // ---- 15. Seluruh kolom nominal finansial baru bertipe integer ----

    public function test_new_financial_columns_are_stored_as_integers(): void
    {
        $invoice = Invoice::factory()->create([
            'subtotal_provider' => 10_000_000,
            'total_commission' => 1_000_000,
            'cleaning_fee' => 500_000,
            'grand_total' => 11_500_000,
            'unique_code' => 342,
            'transfer_total' => 11_500_342,
        ]);

        $this->assertIsInt($invoice->subtotal_provider);
        $this->assertIsInt($invoice->total_commission);
        $this->assertIsInt($invoice->cleaning_fee);
        $this->assertIsInt($invoice->grand_total);
        $this->assertIsInt($invoice->unique_code);
        $this->assertIsInt($invoice->transfer_total);

        $orderItem = OrderItem::factory()->disetujui()->create();
        $this->assertIsInt($orderItem->provider_price);
        $this->assertIsInt($orderItem->commission);

        $event = Event::factory()->create(['budget' => 50_000_000]);
        $this->assertIsInt($event->budget);
    }
}
