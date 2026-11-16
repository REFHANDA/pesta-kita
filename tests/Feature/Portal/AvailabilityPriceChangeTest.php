<?php

namespace Tests\Feature\Portal;

use App\Enums\AvailabilityStatus;
use App\Enums\PriceChangeStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\PriceChangeRequest;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-3b: kalender ketersediaan (vendor/venue tulis, klien baca) dan alur
 * pengajuan harga (penyedia ajukan -> Admin setujui/tolak). Memastikan
 * kepemilikan dijaga server, harga lama berlaku sampai disetujui (A11),
 * dan keputusan menulis audit log.
 */
class AvailabilityPriceChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function futureDate(): string
    {
        return now()->addDays(10)->toDateString();
    }

    // ---- Kalender ketersediaan vendor ----

    public function test_vendor_menandai_ketersediaan_item_milik_sendiri(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)
            ->post('/vendor/kalender-ketersediaan', [
                'item_id' => $item->id,
                'date' => $this->futureDate(),
                'status' => 'tersedia',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('availability_days', [
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'date' => $this->futureDate(),
            'status' => 'tersedia',
        ]);
    }

    public function test_vendor_menolak_item_punya_vendor_lain(): void
    {
        $other = User::factory()->vendor()->create();
        $foreignItem = CatalogItem::factory()->create(['vendor_profile_id' => $other->vendorProfile->id]);
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->post('/vendor/kalender-ketersediaan', [
                'item_id' => $foreignItem->id,
                'date' => $this->futureDate(),
                'status' => 'tersedia',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('availability_days', 0);
    }

    public function test_kalender_vendor_menolak_tanggal_masa_lalu(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)
            ->post('/vendor/kalender-ketersediaan', [
                'item_id' => $item->id,
                'date' => now()->subDays(3)->toDateString(),
                'status' => 'tersedia',
            ])
            ->assertSessionHasErrors('date');
    }

    // ---- Kalender ketersediaan venue ----

    public function test_pemilik_venue_menandai_ketersediaan_ruangan_milik_sendiri(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($owner)
            ->post('/venue/kalender-ketersediaan', [
                'room_id' => $room->id,
                'date' => $this->futureDate(),
                'status' => 'tidak_tersedia',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('availability_days', [
            'subject_type' => 'venue_room',
            'subject_id' => $room->id,
            'status' => 'tidak_tersedia',
        ]);
    }

    public function test_pemilik_venue_ditolak_untuk_ruangan_milik_pemilik_lain(): void
    {
        $other = User::factory()->venue()->create();
        $foreignVenue = Venue::factory()->create(['venue_profile_id' => $other->venueProfile->id]);
        $foreignRoom = VenueRoom::factory()->create(['venue_id' => $foreignVenue->id]);
        $owner = User::factory()->venue()->create();

        $this->actingAs($owner)
            ->post('/venue/kalender-ketersediaan', [
                'room_id' => $foreignRoom->id,
                'date' => $this->futureDate(),
                'status' => 'tersedia',
            ])
            ->assertForbidden();
    }

    // ---- Kalender klien (read-only, visibilitas) ----

    public function test_klien_hanya_melihat_jadwal_penyedia_terverifikasi(): void
    {
        $klien = User::factory()->klien()->create();

        $verifiedVendor = User::factory()->vendor()->create();
        $verifiedVendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $publicItem = CatalogItem::factory()->create(['vendor_profile_id' => $verifiedVendor->vendorProfile->id]);
        AvailabilityDay::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $publicItem->id,
            'date' => $this->futureDate(),
            'status' => AvailabilityStatus::Tersedia,
        ]);

        $pendingVendor = User::factory()->vendor()->create();
        $hiddenItem = CatalogItem::factory()->create([
            'vendor_profile_id' => $pendingVendor->vendorProfile->id,
            'name' => 'Item Penyedia Belum Verifikasi ZZZ',
        ]);

        $response = $this->actingAs($klien)->get('/klien/kalender-ketersediaan');
        $response->assertOk()
            ->assertSee($publicItem->name)
            ->assertDontSee('Item Penyedia Belum Verifikasi ZZZ');
    }

    // ---- Pengajuan harga oleh penyedia ----

    public function test_vendor_mengajukan_perubahan_harga_dengan_harga_lama_dari_server(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'catalog_price' => 2_720_000,
        ]);

        $this->actingAs($vendor)
            ->post('/vendor/pengajuan-harga', [
                'item_id' => $item->id,
                'new_price' => 2_900_000,
                'reason' => 'Kenaikan harga bahan baku.',
            ])
            ->assertRedirect(route('vendor.pengajuan-harga'));

        $change = PriceChangeRequest::first();
        $this->assertSame(2_720_000, $change->old_price, 'old_price dibaca server dari harga aktif.');
        $this->assertSame(2_900_000, $change->new_price);
        $this->assertSame(PriceChangeStatus::Menunggu, $change->status);
        // Aturan A11: harga katalog belum berubah saat pengajuan menunggu.
        $this->assertSame(2_720_000, $item->fresh()->catalog_price);
    }

    public function test_pengajuan_harga_menolak_harga_sama_dengan_aktif(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'catalog_price' => 1_000_000,
        ]);

        $this->actingAs($vendor)
            ->post('/vendor/pengajuan-harga', [
                'item_id' => $item->id,
                'new_price' => 1_000_000,
                'reason' => 'Tidak ada perubahan sesungguhnya.',
            ])
            ->assertSessionHasErrors('new_price');

        $this->assertDatabaseCount('price_change_requests', 0);
    }

    public function test_satu_item_hanya_punya_satu_pengajuan_menunggu(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        PriceChangeRequest::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'status' => PriceChangeStatus::Menunggu,
        ]);

        $this->actingAs($vendor)
            ->post('/vendor/pengajuan-harga', [
                'item_id' => $item->id,
                'new_price' => 9_999_999,
                'reason' => 'Pengajuan ganda.',
            ])
            ->assertSessionHasErrors('item_id');

        $this->assertDatabaseCount('price_change_requests', 1);
    }

    // ---- Keputusan Admin ----

    public function test_admin_menyetujui_pengajuan_mengubah_harga_aktif_dan_mencatat_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'catalog_price' => 85_000,
        ]);
        $change = PriceChangeRequest::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'old_price' => 85_000,
            'new_price' => 95_000,
            'status' => PriceChangeStatus::Menunggu,
        ]);

        $this->actingAs($admin)
            ->post("/admin/pengajuan-harga/{$change->id}/approve")
            ->assertRedirect(route('admin.pengajuan-harga'));

        $this->assertSame(95_000, $item->fresh()->catalog_price);
        $change->refresh();
        $this->assertSame(PriceChangeStatus::Disetujui, $change->status);
        $this->assertSame($admin->id, $change->reviewed_by);
        $this->assertNotNull($change->reviewed_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'price_change_approved',
            'subject_type' => 'price_change_request',
            'subject_id' => $change->id,
            'to_status' => 'disetujui',
        ]);
        $this->assertNotNull(AuditLog::first()->actor_id);
    }

    public function test_admin_menolak_wajib_alasan_dan_harga_tetap_berlaku(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create([
            'vendor_profile_id' => $vendor->vendorProfile->id,
            'catalog_price' => 85_000,
        ]);
        $change = PriceChangeRequest::factory()->create([
            'subject_type' => 'catalog_item',
            'subject_id' => $item->id,
            'old_price' => 85_000,
            'new_price' => 95_000,
            'status' => PriceChangeStatus::Menunggu,
        ]);

        // Tanpa alasan -> ditolak validasi.
        $this->actingAs($admin)
            ->post("/admin/pengajuan-harga/{$change->id}/reject", ['reason' => ''])
            ->assertSessionHasErrors('reason');

        // Dengan alasan -> tersimpan, harga katalog tetap.
        $this->actingAs($admin)
            ->post("/admin/pengajuan-harga/{$change->id}/reject", ['reason' => 'Kenaikan terlalu tajam untuk saat ini.'])
            ->assertRedirect(route('admin.pengajuan-harga'));

        $this->assertSame(85_000, $item->fresh()->catalog_price, 'A11: penolakan tidak mengubah harga.');
        $change->refresh();
        $this->assertSame(PriceChangeStatus::Ditolak, $change->status);
        $this->assertStringContainsString('terlalu tajam', $change->decision_note);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'price_change_rejected',
            'subject_id' => $change->id,
            'to_status' => 'ditolak',
        ]);
    }

    public function test_pengajuan_yang_sudah_diputus_tidak_dapat_diputus_ulang(): void
    {
        $admin = User::factory()->admin()->create();
        $change = PriceChangeRequest::factory()->disetujui()->create();

        $this->actingAs($admin)
            ->post("/admin/pengajuan-harga/{$change->id}/approve")
            ->assertSessionHasErrors('price_change');
    }

    // ---- Guard role & guest ----

    public function test_halaman_be3b_dilindungi_role_dan_guest(): void
    {
        $klien = User::factory()->klien()->create();
        $vendor = User::factory()->vendor()->create();

        // Guest -> redirect login.
        $this->get('/vendor/kalender-ketersediaan')->assertRedirect('/login');

        // Role salah -> 403.
        $this->actingAs($klien)->get('/vendor/kalender-ketersediaan')->assertForbidden();
        $this->actingAs($klien)->get('/admin/pengajuan-harga')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/pengajuan-harga')->assertForbidden();

        // Role tepat -> OK.
        $this->actingAs($vendor)->get('/vendor/kalender-ketersediaan')->assertOk();
        $this->actingAs($klien)->get('/klien/kalender-ketersediaan')->assertOk();
    }
}
