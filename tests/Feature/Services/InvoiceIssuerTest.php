<?php

namespace Tests\Feature\Services;

use App\Enums\EventStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionCategory;
use App\Enums\OrderObjectionStatus;
use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Services\InvoiceIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceIssuerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('commission_percent', 10);
        Setting::set('payment_deadline_hours', 48);
        Setting::set('objection_window_hours', 24);
        Setting::set('unique_code_enabled', true);
    }

    public function test_invoice_issuer_matches_specification_example_and_all_integers(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Diajukan,
        ]);

        $venueProfile = VenueProfile::factory()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'rental_price' => 5_000_000,
            'cleaning_fee' => 500_000, // Biaya kebersihan satu baris per acara
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $vendorProfile = VendorProfile::factory()->create();
        $catering = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Katering',
            'catalog_price' => 3_000_000,
        ]);
        $dekorasi = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Dekorasi',
            'catalog_price' => 2_000_000,
        ]);

        // Buat 3 pesanan yang sudah disetujui, dan masa keberatan telah lewat (subHours(25))
        $pastApproval = now()->subHours(25);
        $pastDeadline = now()->subHour();

        $itemVenue = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'provider_price' => 5_000_000,
            'commission' => 500_000, // 10%
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        $itemCatering = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catering->id,
            'provider_price' => 3_000_000,
            'commission' => 300_000, // 10%
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        $itemDekorasi = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $dekorasi->id,
            'provider_price' => 2_000_000,
            'commission' => 200_000, // 10%
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        $issuer = new InvoiceIssuer;
        $this->assertTrue($issuer->canIssueForEvent($event));

        $invoice = $issuer->issueForEvent($event);
        $this->assertNotNull($invoice);

        // Verifikasi contoh uji wajib §1.3:
        // venue 5.000.000 + catering 3.000.000 + dekorasi 2.000.000 = 10.000.000
        $this->assertSame(10_000_000, $invoice->subtotal_provider);
        // komisi 500.000 + 300.000 + 200.000 = 1.000.000
        $this->assertSame(1_000_000, $invoice->total_commission);
        // kebersihan = 500.000
        $this->assertSame(500_000, $invoice->cleaning_fee);
        // grand total = 11.500.000
        $this->assertSame(11_500_000, $invoice->grand_total);

        // Kode unik 1-999
        $this->assertGreaterThanOrEqual(1, $invoice->unique_code);
        $this->assertLessThanOrEqual(999, $invoice->unique_code);
        // total transfer = grand total + kode unik
        $this->assertSame($invoice->grand_total + $invoice->unique_code, $invoice->transfer_total);

        // Status acara menjadi menunggu_pembayaran
        $event->refresh();
        $this->assertEquals(EventStatus::MenungguPembayaran, $event->status);

        // Format nomor invoice: PK/YYYY/MM/NNNN
        $year = now()->format('Y');
        $month = now()->format('m');
        $this->assertMatchesRegularExpression("/^PK\/{$year}\/{$month}\/\d{4}$/", $invoice->invoice_number);

        // Rincian invoice_items
        $this->assertCount(7, $invoice->items); // 3 harga_penyedia + 3 komisi + 1 kebersihan
        $this->assertEquals(1, $invoice->items()->where('type', InvoiceItemType::Kebersihan)->count());
    }

    public function test_invoice_will_not_issue_while_objection_window_is_still_open(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);

        // Disetujui 2 jam lalu, batas 24 jam masih sisa 22 jam
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(2),
            'objection_deadline' => now()->addHours(22),
            'provider_price' => 1_000_000,
            'commission' => 100_000,
        ]);

        $issuer = new InvoiceIssuer;
        $this->assertFalse($issuer->canIssueForEvent($event));
        $this->assertNull($issuer->issueForEvent($event));
    }

    public function test_invoice_will_not_issue_while_open_objection_exists(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);

        // Masa keberatan lewat tetapi ada keberatan berstatus diajukan
        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(30),
            'objection_deadline' => now()->subHours(6),
            'provider_price' => 1_000_000,
            'commission' => 100_000,
        ]);

        OrderObjection::create([
            'order_item_id' => $item->id,
            'provider_user_id' => User::factory()->create(['role' => Role::Vendor])->id,
            'category' => OrderObjectionCategory::JadwalBentrok,
            'reason' => 'Masih ada kendala jadwal.',
            'status' => OrderObjectionStatus::Diajukan,
        ]);

        $issuer = new InvoiceIssuer;
        $this->assertFalse($issuer->canIssueForEvent($event));
        $this->assertNull($issuer->issueForEvent($event));
    }

    public function test_admin_can_reissue_invoice_only_when_status_is_belum_dibayar(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['status' => EventStatus::MenungguPembayaran]);

        $item = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Disetujui,
            'provider_price' => 2_000_000,
            'commission' => 200_000,
        ]);

        $issuer = new InvoiceIssuer;
        $oldInvoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'version' => 1,
            'status' => InvoiceStatus::BelumDibayar,
            'subtotal_provider' => 2_000_000,
            'total_commission' => 200_000,
            'cleaning_fee' => 0,
            'grand_total' => 2_200_000,
            'unique_code' => 101,
            'transfer_total' => 2_200_101,
        ]);

        $newInvoice = $issuer->reissue($oldInvoice, $admin);

        $oldInvoice->refresh();
        $this->assertEquals(InvoiceStatus::Dibatalkan, $oldInvoice->status);

        $this->assertEquals(InvoiceStatus::BelumDibayar, $newInvoice->status);
        $this->assertEquals(2, $newInvoice->version);
        $this->assertEquals(2_200_000, $newInvoice->grand_total);
    }

    public function test_invoice_calculation_with_multi_quantity_equipment_catering_ballroom_and_cleaning_fee(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Diajukan,
        ]);

        $venueProfile = VenueProfile::factory()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'name' => 'Graha Melati',
            'cleaning_fee' => 500_000,
        ]);
        $room = VenueRoom::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Ballroom Utama',
        ]);

        $vendorProfile = VendorProfile::factory()->create();
        $catering = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Paket Katering Nusantara',
            'catalog_price' => 15_000_000,
        ]);
        $kursi = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Sewa Kursi Tiffany',
            'catalog_price' => 15_000,
        ]);

        $pastApproval = now()->subHours(25);
        $pastDeadline = now()->subHour();

        // 1. Kursi: x100 @ 15.000 -> line total 1.500.000, komisi 150.000
        $itemKursi = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $kursi->id,
            'quantity' => 100,
            'catalog_price_snapshot' => 15_000,
            'provider_price' => 15_000,
            'commission' => 150_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        // 2. Katering: 15.000.000 -> komisi 1.500.000
        $itemCatering = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catering->id,
            'quantity' => 1,
            'catalog_price_snapshot' => 15_000_000,
            'provider_price' => 15_000_000,
            'commission' => 1_500_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        // 3. Ballroom: 25.000.000 -> komisi 2.500.000
        $itemBallroom = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'quantity' => 1,
            'catalog_price_snapshot' => 25_000_000,
            'provider_price' => 25_000_000,
            'commission' => 2_500_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        $issuer = new InvoiceIssuer;
        $this->assertTrue($issuer->canIssueForEvent($event));

        $invoice = $issuer->issueForEvent($event);
        $this->assertNotNull($invoice);

        // Verifikasi angka:
        // subtotal_provider: 1.500.000 + 15.000.000 + 25.000.000 = 41.500.000
        $this->assertSame(41_500_000, $invoice->subtotal_provider);
        // komisi: 150.000 + 1.500.000 + 2.500.000 = 4.150.000
        $this->assertSame(4_150_000, $invoice->total_commission);
        // kebersihan: 500.000
        $this->assertSame(500_000, $invoice->cleaning_fee);
        // grand total: 41.500.000 + 4.150.000 + 500.000 = 46.150.000 (45.650.000 tanpa kebersihan)
        $this->assertSame(46_150_000, $invoice->grand_total);
        // transfer total: grand total + kode unik (1-999)
        $this->assertGreaterThanOrEqual(1, $invoice->unique_code);
        $this->assertLessThanOrEqual(999, $invoice->unique_code);
        $this->assertSame($invoice->grand_total + $invoice->unique_code, $invoice->transfer_total);

        // Rincian invoice_items: 3 harga_penyedia + 3 komisi + 1 kebersihan = 7 baris
        $this->assertCount(7, $invoice->items);

        $providerItems = $invoice->items()->where('type', InvoiceItemType::HargaPenyedia)->get();
        $this->assertCount(3, $providerItems);

        // Baris kursi bernilai 1.500.000 dan deskripsi berformat "x100"
        $chairItem = $providerItems->firstWhere('order_item_id', $itemKursi->id);
        $this->assertNotNull($chairItem);
        $this->assertSame(1_500_000, $chairItem->amount);
        $this->assertStringContainsString('Sewa Kursi Tiffany x100', $chairItem->description);

        // Baris katering bernilai 15.000.000
        $cateringItem = $providerItems->firstWhere('order_item_id', $itemCatering->id);
        $this->assertNotNull($cateringItem);
        $this->assertSame(15_000_000, $cateringItem->amount);

        // Baris venue ballroom bernilai 25.000.000
        $ballroomItem = $providerItems->firstWhere('order_item_id', $itemBallroom->id);
        $this->assertNotNull($ballroomItem);
        $this->assertSame(25_000_000, $ballroomItem->amount);

        // Baris kebersihan bernilai 500.000
        $cleaningItem = $invoice->items()->where('type', InvoiceItemType::Kebersihan)->first();
        $this->assertNotNull($cleaningItem);
        $this->assertSame(500_000, $cleaningItem->amount);
    }

    public function test_invoice_issuer_uses_half_up_rounding_for_commission(): void
    {
        $event = Event::factory()->create(['status' => EventStatus::Diajukan]);
        $vendorProfile = VendorProfile::factory()->create();

        // 125 * 10% = 12.5 -> half-up 13
        $item1 = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'quantity' => 1,
            'provider_price' => 125,
            'commission' => 13,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(25),
            'objection_deadline' => now()->subHour(),
        ]);

        // 15 * 7 = 105; 105 * 10% = 10.5 -> half-up 11
        $item2 = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'quantity' => 7,
            'provider_price' => 15,
            'commission' => 11,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now()->subHours(25),
            'objection_deadline' => now()->subHour(),
        ]);

        $issuer = new InvoiceIssuer;
        $invoice = $issuer->issueForEvent($event);

        $this->assertNotNull($invoice);
        // subtotal = 125 + 105 = 230
        $this->assertSame(230, $invoice->subtotal_provider);
        // total komisi = 13 + 11 = 24
        $this->assertSame(24, $invoice->total_commission);
        $this->assertSame(254, $invoice->grand_total);
    }

    public function test_reissue_invoice_preserves_multi_quantity_line_totals_and_descriptions(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['status' => EventStatus::MenungguPembayaran]);
        $vendorProfile = VendorProfile::factory()->create();

        $kursi = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Sewa Kursi Lipat',
            'catalog_price' => 10_000,
        ]);

        $itemKursi = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $kursi->id,
            'quantity' => 50,
            'catalog_price_snapshot' => 10_000,
            'provider_price' => 10_000,
            'commission' => 50_000,
            'status' => OrderItemStatus::Disetujui,
        ]);

        $issuer = new InvoiceIssuer;
        $oldInvoice = Invoice::factory()->create([
            'event_id' => $event->id,
            'version' => 1,
            'status' => InvoiceStatus::BelumDibayar,
            'subtotal_provider' => 500_000,
            'total_commission' => 50_000,
            'cleaning_fee' => 0,
            'grand_total' => 550_000,
            'unique_code' => 50,
            'transfer_total' => 550_050,
        ]);

        $newInvoice = $issuer->reissue($oldInvoice, $admin);

        $this->assertEquals(InvoiceStatus::BelumDibayar, $newInvoice->status);
        $this->assertEquals(2, $newInvoice->version);
        $this->assertSame(500_000, $newInvoice->subtotal_provider);
        $this->assertSame(50_000, $newInvoice->total_commission);
        $this->assertSame(550_000, $newInvoice->grand_total);

        $chairLine = $newInvoice->items()->where('type', InvoiceItemType::HargaPenyedia)->first();
        $this->assertNotNull($chairLine);
        $this->assertSame(500_000, $chairLine->amount);
        $this->assertStringContainsString('Sewa Kursi Lipat x50', $chairLine->description);
    }

    public function test_invoice_items_amount_sum_always_equals_grand_total_invariant(): void
    {
        $klien = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $klien->id,
            'status' => EventStatus::Diajukan,
        ]);

        $venueProfile = VenueProfile::factory()->create();
        $venue = Venue::factory()->create([
            'venue_profile_id' => $venueProfile->id,
            'rental_price' => 7_500_000,
            'cleaning_fee' => 350_000,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $vendorProfile = VendorProfile::factory()->create();
        $catering = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Katering Prasmanan',
            'catalog_price' => 2_720_000,
        ]);
        $sound = CatalogItem::factory()->peralatan()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Sound System',
            'catalog_price' => 700_000,
        ]);

        $pastApproval = now()->subHours(25);
        $pastDeadline = now()->subHour();

        // 1. Venue
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'quantity' => 1,
            'provider_price' => 7_500_000,
            'commission' => 750_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        // 2. Katering
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catering->id,
            'quantity' => 1,
            'provider_price' => 2_720_000,
            'commission' => 272_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        // 3. Sound system x2
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $sound->id,
            'quantity' => 2,
            'provider_price' => 700_000,
            'commission' => 140_000,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => $pastApproval,
            'objection_deadline' => $pastDeadline,
        ]);

        $issuer = new InvoiceIssuer;
        $invoice = $issuer->issueForEvent($event);

        $this->assertNotNull($invoice);

        // Invarian utama: sum(invoice_items.amount) == grand_total
        $itemsSum = (int) $invoice->items()->sum('amount');
        $this->assertSame($invoice->grand_total, $itemsSum);
        $this->assertSame(
            $invoice->subtotal_provider + $invoice->total_commission + $invoice->cleaning_fee,
            $itemsSum
        );
    }

    public function test_line_total_throws_exception_when_provider_price_is_null_on_approved_or_confirmed_orders(): void
    {
        $approvedItem = new OrderItem([
            'status' => OrderItemStatus::Disetujui,
            'quantity' => 1,
            'provider_price' => null,
            'catalog_price_snapshot' => 1_000_000,
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('provider_price');
        $approvedItem->lineTotal();
    }

    public function test_line_total_throws_exception_when_provider_price_is_null_on_confirmed_orders(): void
    {
        $confirmedItem = new OrderItem([
            'status' => OrderItemStatus::Terkonfirmasi,
            'quantity' => 2,
            'provider_price' => null,
            'catalog_price_snapshot' => 1_000_000,
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('provider_price');
        $confirmedItem->lineTotal();
    }

    public function test_line_total_does_not_fallback_to_catalog_price_snapshot(): void
    {
        $draftItem = new OrderItem([
            'status' => OrderItemStatus::Draft,
            'quantity' => 3,
            'provider_price' => null,
            'catalog_price_snapshot' => 500_000,
        ]);

        // Tanpa fallback, lineTotal mengembalikan 0 bila provider_price null
        $this->assertSame(0, $draftItem->lineTotal());
    }

    public function test_document_number_counter_is_atomic_and_does_not_collide(): void
    {
        /** @var InvoiceIssuer $issuer */
        $issuer = app(InvoiceIssuer::class);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            if ($i % 2 === 0) {
                $numbers[] = $issuer->generateInvoiceNumber();
            } else {
                $numbers[] = $issuer->generateReceiptNumber();
            }
        }

        // Pastikan tidak ada duplikasi nomor
        $this->assertCount(10, $numbers);
        $this->assertCount(10, array_unique($numbers));

        // Format harus PK/YYYY/MM/NNNN dan berurutan dari 0001 sampai 0010
        $yearMonth = now()->format('Y/m');
        for ($i = 0; $i < 10; $i++) {
            $expectedSeq = sprintf('%04d', $i + 1);
            $this->assertEquals("PK/{$yearMonth}/{$expectedSeq}", $numbers[$i]);
        }

        // DocumentCounter mencatat current_number = 10
        $this->assertDatabaseHas('document_counters', [
            'type' => 'PK',
            'period' => $yearMonth,
            'current_number' => 10,
        ]);
    }
}
