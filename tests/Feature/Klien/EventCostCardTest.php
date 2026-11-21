<?php

namespace Tests\Feature\Klien;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Services\ClientCostEstimator;
use App\Services\InvoiceIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap G1: kartu "Estimasi biaya" di ringkasan acara Klien konsisten dengan
 * tagihan (harga penyedia + komisi + kebersihan venue sekali per acara).
 */
class EventCostCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('commission_percent', 10);
        Setting::set('objection_window_hours', 24);
        Setting::set('payment_deadline_hours', 48);
    }

    protected function event(EventStatus $status = EventStatus::Draft): Event
    {
        $client = User::factory()->klien()->create();

        return Event::factory()->create([
            'client_id' => $client->id,
            'status' => $status,
            'budget' => 100_000_000,
            'guest_count' => 100,
            'event_date' => now()->addDays(30)->format('Y-m-d'),
        ]);
    }

    /**
     * @return array{0: Venue, 1: VenueRoom, 2: VenueProfile}
     */
    protected function venueWithRoom(int $rental, int $cleaning): array
    {
        $profile = VenueProfile::factory()->create([
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $venue = Venue::factory()->create([
            'venue_profile_id' => $profile->id,
            'rental_price' => $rental,
            'cleaning_fee' => $cleaning,
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        return [$venue, $room, $profile];
    }

    protected function roomItem(Event $event, VenueRoom $room, VenueProfile $profile, int $price, array $extra = []): OrderItem
    {
        return OrderItem::factory()->create(array_merge([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $profile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'provider_price' => $price,
            'catalog_price_snapshot' => $price,
            'commission' => 0,
            'status' => OrderItemStatus::Draft,
        ], $extra));
    }

    protected function serviceItem(Event $event, int $price, array $extra = []): OrderItem
    {
        $vendor = VendorProfile::factory()->create();
        $catalog = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->id,
            'catalog_price' => $price,
        ]);

        return OrderItem::factory()->create(array_merge([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $catalog->id,
            'provider_price' => $price,
            'catalog_price_snapshot' => $price,
            'commission' => 0,
            'status' => OrderItemStatus::Draft,
        ], $extra));
    }

    public function test_acara_draft_menjumlah_harga_komisi_dan_kebersihan_sekali(): void
    {
        $event = $this->event();
        [, $room, $profile] = $this->venueWithRoom(20_000_000, 1_500_000);
        $this->roomItem($event, $room, $profile, 20_000_000);
        $this->serviceItem($event, 10_000_000);

        $hasil = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));

        // 20.000.000 + 2.000.000 + 1.500.000 + 10.000.000 + 1.000.000
        $this->assertSame(34_500_000, $hasil['amount']);
        $this->assertIsInt($hasil['amount']);
        $this->assertNull($hasil['invoice_number']);

        $this->actingAs($event->client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Estimasi biaya')
            ->assertSee('Rp 34.500.000')
            ->assertSee('Sudah termasuk komisi layanan dan biaya kebersihan venue; angka final tertera pada tagihan.');
    }

    public function test_dua_venue_hanya_menghitung_kebersihan_venue_pertama_sekali(): void
    {
        $event = $this->event();
        [, $roomA, $profileA] = $this->venueWithRoom(10_000_000, 1_000_000);
        [, $roomB, $profileB] = $this->venueWithRoom(5_000_000, 700_000);
        $this->roomItem($event, $roomA, $profileA, 10_000_000);
        $this->roomItem($event, $roomB, $profileB, 5_000_000);

        $hasil = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));

        // (10.000.000 + 1.000.000) + (5.000.000 + 500.000) + kebersihan venue pertama 1.000.000
        $this->assertSame(17_500_000, $hasil['amount']);
    }

    public function test_venue_tanpa_biaya_kebersihan_tidak_menambah_apa_apa(): void
    {
        $event = $this->event();
        [, $room, $profile] = $this->venueWithRoom(10_000_000, 0);
        $this->roomItem($event, $room, $profile, 10_000_000);

        $hasil = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));

        $this->assertSame(11_000_000, $hasil['amount']);
    }

    public function test_komisi_terkunci_dipakai_walau_persen_pengaturan_berubah(): void
    {
        $event = $this->event(EventStatus::Diajukan);
        $this->serviceItem($event, 10_000_000, [
            'status' => OrderItemStatus::Disetujui,
            'commission' => 1_000_000,
            'approved_at' => now(),
            'objection_deadline' => now()->addDay(),
        ]);
        // Pesanan belum disetujui: komisi dihitung dari Pengaturan terkini.
        $this->serviceItem($event, 2_000_000, [
            'status' => OrderItemStatus::MenungguReview,
        ]);

        Setting::set('commission_percent', 20);

        $hasil = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));

        // Terkunci: 10.000.000 + 1.000.000 (bukan 2.000.000). Belum terkunci: 2.000.000 + 400.000.
        $this->assertSame(13_400_000, $hasil['amount']);
    }

    public function test_tagihan_dibatalkan_diabaikan_dan_tagihan_aktif_menjadi_sumber_angka(): void
    {
        $event = $this->event(EventStatus::MenungguPembayaran);
        $this->serviceItem($event, 10_000_000, [
            'status' => OrderItemStatus::Disetujui,
            'commission' => 1_000_000,
        ]);

        Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::Dibatalkan,
            'grand_total' => 99_000_000,
        ]);

        $tanpaAktif = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));
        $this->assertSame(11_000_000, $tanpaAktif['amount']);
        $this->assertNull($tanpaAktif['invoice_number']);

        $aktif = Invoice::factory()->create([
            'event_id' => $event->id,
            'status' => InvoiceStatus::BelumDibayar,
            'grand_total' => 11_000_000,
            'unique_code' => 123,
            'transfer_total' => 11_000_123,
        ]);

        $hasil = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));
        $this->assertSame(11_000_000, $hasil['amount']);
        $this->assertSame($aktif->invoice_number, $hasil['invoice_number']);
    }

    /**
     * Tes pagar: alur nyata sampai tagihan terbit, angka kartu = grand_total.
     */
    public function test_angka_kartu_sama_dengan_grand_total_tagihan_dari_alur_nyata(): void
    {
        $event = $this->event();
        $client = $event->client;
        [, $room] = $this->venueWithRoom(20_000_000, 1_500_000);

        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $catalog = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'catalog_price' => 10_000_000,
        ]);

        $this->actingAs($client)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
        ])->assertRedirect();

        $this->actingAs($client)->post(route('klien.acara.pesanan.store', $event), [
            'subject_type' => 'catalog_item',
            'subject_id' => $catalog->id,
        ])->assertRedirect();

        // Sebelum diajukan: kartu = estimasi.
        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Rp 34.500.000');

        $this->actingAs($client)->post(route('klien.acara.ajukan', $event))->assertRedirect();

        $admin = User::factory()->admin()->create();
        foreach (OrderItem::where('event_id', $event->id)->get() as $orderItem) {
            $this->actingAs($admin)->post(route('admin.pesanan.approve', $orderItem))->assertRedirect();
            $orderItem->update(['objection_deadline' => now()->subMinute()]);
        }

        // Pengaturan komisi diubah setelah persetujuan: kartu tetap mengikuti komisi terkunci.
        Setting::set('commission_percent', 25);

        $invoice = app(InvoiceIssuer::class)->issueForEvent($event->fresh());
        $this->assertInstanceOf(Invoice::class, $invoice);

        $kartu = app(ClientCostEstimator::class)->forEvent($event->fresh()->load('orderItems.subject'));

        $this->assertSame($invoice->grand_total, $kartu['amount']);
        $this->assertSame(34_500_000, $kartu['amount']);
        $this->assertSame($invoice->invoice_number, $kartu['invoice_number']);
        $this->assertSame($invoice->grand_total + $invoice->unique_code, $invoice->transfer_total);

        $this->actingAs($client)->get(route('klien.acara.detail', $event))
            ->assertOk()
            ->assertSee('Sesuai tagihan '.$invoice->invoice_number.' (belum termasuk kode unik).');
    }
}
