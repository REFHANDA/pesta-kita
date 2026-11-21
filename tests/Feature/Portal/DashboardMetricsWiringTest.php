<?php

namespace Tests\Feature\Portal;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionStatus;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\PriceChangeRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_real_metrics_and_no_stale_backend_banner(): void
    {
        $admin = User::factory()->admin()->create();

        // 1 vendor menunggu, 1 venue menunggu
        VendorProfile::factory()->menunggu()->create();
        VenueProfile::factory()->menunggu()->create();

        // 1 vendor terverifikasi
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        // 1 event dengan 2 pesanan
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Diajukan,
        ]);

        $item1 = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $order1 = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item1->id,
            'status' => OrderItemStatus::MenungguReview,
        ]);

        $order2 = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item1->id,
            'status' => OrderItemStatus::Disetujui,
            'approved_at' => now(),
        ]);

        // 1 keberatan terbuka
        OrderObjection::factory()->create([
            'order_item_id' => $order2->id,
            'status' => OrderObjectionStatus::Diajukan,
        ]);

        // 1 price change request menunggu
        PriceChangeRequest::create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item1->id,
            'user_id' => $vendor->id,
            'old_price' => 100000,
            'new_price' => 120000,
            'reason' => 'Kenaikan bahan',
            'status' => 'menunggu',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('Sebagian ringkasan belum terhubung ke backend');
        $response->assertDontSee('Antrean lain belum tersedia di backend');

        $response->assertSee('Pesanan menunggu review');
        $response->assertSee('Keberatan penyedia terbuka');
        $response->assertSee('Penyedia menunggu verifikasi');
        $response->assertSee('Pengajuan harga menunggu review');
        $response->assertSeeText('5 menunggu');
    }

    public function test_klien_dashboard_shows_real_events_and_no_stale_backend_banner(): void
    {
        $client = User::factory()->klien()->create(['name' => 'Klien Sejati']);

        Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Pesta Ultah Anak',
            'status' => EventStatus::Diajukan,
        ]);

        $response = $this->actingAs($client)->get('/klien');

        $response->assertOk();
        $response->assertSee('Pesta Ultah Anak');
        $response->assertDontSee('Sebagian fitur belum terhubung ke backend');
    }

    public function test_vendor_dashboard_shows_anonymized_orders_without_client_identity(): void
    {
        $client = User::factory()->klien()->create(['name' => 'Budi Rahasia']);
        $vendorUser = User::factory()->vendor()->create();
        $vendorProfile = $vendorUser->vendorProfile;

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Acara Ulang Tahun Budi',
            'status' => EventStatus::Diajukan,
        ]);

        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendorProfile->id,
            'name' => 'Paket Dekorasi Balon',
        ]);

        $order = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendorProfile->id,
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'status' => OrderItemStatus::MenungguReview,
        ]);

        $response = $this->actingAs($vendorUser)->get('/vendor');

        $response->assertOk();
        $response->assertDontSee('Sebagian fitur belum terhubung ke backend');
        $response->assertSee("Acara #{$event->id}");
        $response->assertSee('Paket Dekorasi Balon');
        $response->assertDontSee('Budi Rahasia');
        $response->assertDontSee('Acara Ulang Tahun Budi');
    }

    public function test_venue_dashboard_shows_anonymized_orders_without_client_identity(): void
    {
        $client = User::factory()->klien()->create(['name' => 'Siti Rahasia']);
        $venueUser = User::factory()->venue()->create();
        $venueProfile = $venueUser->venueProfile;

        $venue = Venue::factory()->terverifikasi()->create([
            'venue_profile_id' => $venueProfile->id,
            'name' => 'Gedung Serbaguna Asli',
        ]);
        $room = $venue->rooms()->create(['name' => 'Ruang Melati', 'capacity' => 150]);

        $event = Event::factory()->create([
            'client_id' => $client->id,
            'name' => 'Pernikahan Siti',
            'status' => EventStatus::Diajukan,
        ]);

        $order = OrderItem::factory()->create([
            'event_id' => $event->id,
            'provider_type' => 'venue_profile',
            'provider_id' => $venueProfile->id,
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'status' => OrderItemStatus::MenungguReview,
        ]);

        $response = $this->actingAs($venueUser)->get('/venue');

        $response->assertOk();
        $response->assertDontSee('Sebagian fitur belum terhubung ke backend');
        $response->assertSee("Acara #{$event->id}");
        $response->assertSee('Ruang Melati');
        $response->assertDontSee('Siti Rahasia');
        $response->assertDontSee('Pernikahan Siti');
    }
}
