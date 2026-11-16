<?php

namespace Tests\Feature\Portal;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Enums\QuoteRequestStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring read-only BE-3c: halaman detail + daftar status acara Klien,
 * permintaan penawaran (Klien/Vendor/Venue), dan tinjauan katalog Admin.
 * Semua halaman wajib membaca data nyata dan menegakkan kepemilikan di
 * server; data contoh prototipe tidak boleh muncul lagi.
 */
class EventQuoteCatalogWiringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Satu permintaan penawaran nyata atas item katalog milik $vendor.
     */
    protected function quoteForItem(User $client, Event $event, CatalogItem $item, QuoteRequestStatus $status = QuoteRequestStatus::Diajukan): QuoteRequest
    {
        return QuoteRequest::factory()->create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'catalog_price_snapshot' => 7_250_000,
            'status' => $status,
        ]);
    }

    public function test_event_detail_shows_real_event_and_linked_quote_requests(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Sedekah Rumah Tersusun',
            'status' => EventStatus::Diajukan,
            'guest_count' => 120,
        ]);
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Paket Suara Rimba Jernih',
        ]);
        $quote = $this->quoteForItem($client, $event, $item, QuoteRequestStatus::DiusulkanPenyedia);
        QuoteProposal::factory()->create([
            'quote_request_id' => $quote->id,
            'proposed_amount' => 6_900_000,
        ]);

        $this->actingAs($client)->get('/klien/acara-detail/'.$event->id)
            ->assertOk()
            ->assertSee('Sedekah Rumah Tersusun')
            ->assertSee('120 tamu')
            ->assertSee('Paket Suara Rimba Jernih')
            ->assertSee('Rp 7.250.000')
            ->assertSee('Rp 6.900.000')
            ->assertSee('Ditinjau Admin')
            ->assertDontSee('Ulang Tahun ke-7 Nadia')
            ->assertDontSee('Tagihan menunggu pembayaran')
            ->assertDontSee('PK/2026/11/0007')
            ->assertDontSee('Rp 13.132.317');
    }

    public function test_event_detail_of_another_client_is_not_found(): void
    {
        $other = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $other->id]);
        $client = User::factory()->klien()->create();

        $this->actingAs($client)->get('/klien/acara-detail/'.$event->id)->assertNotFound();
    }

    public function test_status_list_pages_only_show_matching_own_statuses(): void
    {
        $client = User::factory()->klien()->create();
        Event::factory()->create(['client_id' => $client->id, 'name' => 'Arisan Draft Berjalan', 'status' => EventStatus::Draft]);
        Event::factory()->create(['client_id' => $client->id, 'name' => 'Kondangan Diajukan Lama', 'status' => EventStatus::Diajukan]);
        Event::factory()->create(['client_id' => $client->id, 'name' => 'Wisuda Terkonfirmasi Besar', 'status' => EventStatus::Terkonfirmasi]);
        Event::factory()->create(['client_id' => $client->id, 'name' => 'Piknik Dibatalkan Dini', 'status' => EventStatus::Dibatalkan]);
        Event::factory()->create(['name' => 'Rahasia Klien Lain Terkonfirmasi', 'status' => EventStatus::Terkonfirmasi]);

        $this->actingAs($client)->get('/klien/acara-draft')
            ->assertOk()
            ->assertSee('Arisan Draft Berjalan')
            ->assertDontSee('Kondangan Diajukan Lama')
            ->assertDontSee('Wisuda Terkonfirmasi Besar');
        $this->actingAs($client)->get('/klien/acara-diajukan')
            ->assertOk()
            ->assertSee('Kondangan Diajukan Lama')
            ->assertDontSee('Arisan Draft Berjalan');
        $this->actingAs($client)->get('/klien/acara-terkonfirmasi')
            ->assertOk()
            ->assertSee('Wisuda Terkonfirmasi Besar')
            ->assertDontSee('Piknik Dibatalkan Dini')
            ->assertDontSee('Rahasia Klien Lain Terkonfirmasi');
        $this->actingAs($client)->get('/klien/acara-dibatalkan')
            ->assertOk()
            ->assertSee('Piknik Dibatalkan Dini')
            ->assertDontSee('Wisuda Terkonfirmasi Besar');
    }

    public function test_klien_quote_list_reads_own_requests_with_tab_filters(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Hiasan Meja Menawan',
        ]);
        $itemDitinjau = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Lampion Taman Sentosa',
        ]);
        $this->quoteForItem($client, $event, $item);
        $ditinjau = $this->quoteForItem($client, $event, $itemDitinjau, QuoteRequestStatus::DiusulkanPenyedia);
        // Milik klien lain -> tidak boleh tampil.
        $foreignClient = User::factory()->klien()->create();
        $foreignItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Kursi Rotan Eksotis',
        ]);
        $foreign = $this->quoteForItem($foreignClient, $event, $foreignItem);

        $this->actingAs($client)->get('/klien/permintaan-penawaran')
            ->assertOk()
            ->assertSee('Hiasan Meja Menawan')
            ->assertSee('Lampion Taman Sentosa')
            ->assertSee('Rp 7.250.000')
            ->assertDontSee('Kursi Rotan Eksotis')
            ->assertDontSee('Ulang Tahun ke-7 Nadia');

        $this->actingAs($client)->get('/klien/permintaan-penawaran?status=menunggu')
            ->assertOk()
            ->assertSee('Hiasan Meja Menawan')
            ->assertDontSee('Lampion Taman Sentosa');

        // Detail milik sendiri OK; detail milik klien lain -> 404.
        $this->actingAs($client)->get('/klien/permintaan-penawaran-detail/'.$ditinjau->id)
            ->assertOk()
            ->assertSee('Lampion Taman Sentosa')
            ->assertSee('Rp 7.250.000');
        $this->actingAs($client)->get('/klien/permintaan-penawaran-detail/'.$foreign->id)->assertNotFound();
    }

    public function test_klien_quote_detail_shows_proposal_but_no_fake_decision(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Tenda Kilau Biru',
        ]);
        $quote = $this->quoteForItem($client, $event, $item, QuoteRequestStatus::DiusulkanPenyedia);
        QuoteProposal::factory()->create([
            'quote_request_id' => $quote->id,
            'proposed_amount' => 6_450_000,
        ]);

        $this->actingAs($client)->get('/klien/permintaan-penawaran-detail/'.$quote->id)
            ->assertOk()
            ->assertSee('Tenda Kilau Biru')
            ->assertSee('Rp 6.450.000')
            ->assertSee('Belum diputuskan')
            ->assertDontSee('Rp 85.000');
    }

    public function test_vendor_only_sees_requests_targeting_own_catalog_items(): void
    {
        $vendor = User::factory()->vendor()->create();
        $ownItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Katering Nusantara Merakyat',
        ]);
        $otherVendor = User::factory()->vendor()->create();
        $otherItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $otherVendor->vendorProfile->id,
            'name' => 'Dekorasi Kaktus Mini',
        ]);
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);
        $own = $this->quoteForItem($client, $event, $ownItem);
        $foreign = $this->quoteForItem($client, $event, $otherItem);

        $this->actingAs($vendor)->get('/vendor/permintaan-penawaran')
            ->assertOk()
            ->assertSee('Katering Nusantara Merakyat')
            ->assertDontSee('Dekorasi Kaktus Mini')
            ->assertDontSee('Ulang Tahun ke-7 Nadia');

        $this->actingAs($vendor)->get('/vendor/permintaan-penawaran-detail/'.$own->id)
            ->assertOk()
            ->assertSee('Katering Nusantara Merakyat')
            ->assertSee('Rp 7.250.000')
            // Tidak ada kontak fiktif / form usulan mati.
            ->assertDontSee('0812-');
        $this->actingAs($vendor)->get('/vendor/permintaan-penawaran-detail/'.$foreign->id)->assertNotFound();
    }

    public function test_venue_only_sees_requests_targeting_own_rooms(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id, 'name' => 'Ruang Cempaka Putih']);
        $otherOwner = User::factory()->venue()->create();
        $otherVenue = Venue::factory()->create(['venue_profile_id' => $otherOwner->venueProfile->id]);
        $otherRoom = VenueRoom::factory()->create(['venue_id' => $otherVenue->id, 'name' => 'Ruang Melati Biru']);
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create(['client_id' => $client->id]);

        $own = QuoteRequest::factory()->create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'catalog_price_snapshot' => 18_400_000,
        ]);
        $foreign = QuoteRequest::factory()->create([
            'client_id' => $client->id,
            'event_id' => $event->id,
            'subject_type' => 'venue_room',
            'subject_id' => $otherRoom->id,
        ]);

        $this->actingAs($owner)->get('/venue/permintaan-penawaran')
            ->assertOk()
            ->assertSee('Ruang Cempaka Putih')
            ->assertSee('Rp 18.400.000')
            ->assertDontSee('Ruang Melati Biru');

        $this->actingAs($owner)->get('/venue/permintaan-penawaran-detail/'.$own->id)
            ->assertOk()
            ->assertSee('Ruang Cempaka Putih');
        $this->actingAs($owner)->get('/venue/permintaan-penawaran-detail/'.$foreign->id)->assertNotFound();
    }

    public function test_admin_katalog_lists_real_items_and_venues_with_filters(): void
    {
        $vendor = User::factory()->vendor()->create();
        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Layanan Panggung Emas',
            'kind' => CatalogKind::Layanan,
            'status' => CatalogItemStatus::Aktif,
        ]);
        CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Kursi Plastik Putih',
            'kind' => CatalogKind::Peralatan,
            'status' => CatalogItemStatus::Nonaktif,
        ]);
        $venueOwner = User::factory()->venue()->create();
        Venue::factory()->create([
            'venue_profile_id' => $venueOwner->venueProfile->id,
            'name' => 'Balai Kota Indah Permai',
            'rental_price' => 25_000_000,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/katalog')
            ->assertOk()
            ->assertSee('Layanan Panggung Emas')
            ->assertSee('Kursi Plastik Putih')
            ->assertSee('Balai Kota Indah Permai')
            ->assertSee('Rp 25.000.000')
            ->assertDontSee('Paket Catering Nusantara')
            ->assertDontSee('Catering Bu Sari');

        $this->actingAs($admin)->get('/admin/katalog?jenis=peralatan')
            ->assertOk()
            ->assertSee('Kursi Plastik Putih')
            ->assertDontSee('Layanan Panggung Emas');

        $this->actingAs($admin)->get('/admin/katalog?status=aktif')
            ->assertOk()
            ->assertSee('Layanan Panggung Emas')
            ->assertDontSee('Kursi Plastik Putih');
    }

    public function test_admin_katalog_detail_shows_real_price_without_fake_data(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'name' => 'Sewa Tenda Baja Kuat',
            'catalog_price' => 12_300_000,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/katalog-detail/'.$item->id)
            ->assertOk()
            ->assertSee('Sewa Tenda Baja Kuat')
            ->assertSee('Rp 12.300.000')
            ->assertSee($vendor->vendorProfile->business_name)
            ->assertDontSee('Rp 85.000')
            ->assertDontSee('KAT-0018')
            ->assertDontSee('Dapur Nusantara');
    }

    public function test_wired_pages_are_guarded_by_auth_and_role(): void
    {
        $paths = [
            '/klien/acara-draft',
            '/klien/permintaan-penawaran',
            '/vendor/permintaan-penawaran',
            '/venue/permintaan-penawaran',
            '/admin/katalog',
        ];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $client = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();
        $admin = User::factory()->admin()->create();

        // Setiap portal hanya untuk perannya sendiri.
        $this->actingAs($client)->get('/vendor/permintaan-penawaran')->assertForbidden();
        $this->actingAs($client)->get('/venue/permintaan-penawaran')->assertForbidden();
        $this->actingAs($client)->get('/admin/katalog')->assertForbidden();
        $this->actingAs($vendor)->get('/klien/acara-draft')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/katalog')->assertForbidden();
        $this->actingAs($venue)->get('/klien/permintaan-penawaran')->assertForbidden();
        $this->actingAs($admin)->get('/klien/acara-draft')->assertForbidden();
        $this->actingAs($admin)->get('/vendor/permintaan-penawaran')->assertForbidden();
    }
}
