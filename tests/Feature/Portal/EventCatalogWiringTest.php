<?php

namespace Tests\Feature\Portal;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\EventStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring read-only: halaman detail + daftar status acara Klien,
 * dan tinjauan katalog Admin.
 * Semua halaman wajib membaca data nyata dan menegakkan kepemilikan di
 * server; data contoh prototipe tidak boleh muncul lagi.
 */
class EventCatalogWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_detail_shows_real_event(): void
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
        OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'catalog_price_snapshot' => 7_250_000,
        ]);

        $this->actingAs($client)->get('/klien/acara-detail/'.$event->id)
            ->assertOk()
            ->assertSee('Sedekah Rumah Tersusun')
            ->assertSee('120 tamu')
            ->assertSee('Paket Suara Rimba Jernih')
            ->assertSee('Rp 7.250.000')
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
            '/admin/katalog',
            '/vendor/layanan',
            '/venue/data',
        ];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $client = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();
        $admin = User::factory()->admin()->create();

        // Setiap portal hanya untuk perannya sendiri.
        $this->actingAs($client)->get('/vendor/layanan')->assertForbidden();
        $this->actingAs($client)->get('/venue/data')->assertForbidden();
        $this->actingAs($client)->get('/admin/katalog')->assertForbidden();
        $this->actingAs($vendor)->get('/klien/acara-draft')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/katalog')->assertForbidden();
        $this->actingAs($venue)->get('/admin/katalog')->assertForbidden();
        $this->actingAs($admin)->get('/klien/acara-draft')->assertForbidden();
        $this->actingAs($admin)->get('/vendor/layanan')->assertForbidden();
    }
}
