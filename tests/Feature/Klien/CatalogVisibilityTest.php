<?php

namespace Tests\Feature\Klien;

use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-3 langkah 7: visibilitas katalog Klien ditegakkan di query backend.
 * Subjek tidak publik -> 404 (bukan 403); filter server-side; hanya foto
 * publik yang diteruskan ke view; kontak privat tidak pernah dibagikan.
 */
class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function verifiedVendor(): VendorProfile
    {
        return VendorProfile::factory()->terverifikasi()->create();
    }

    // ---- Daftar item ----

    public function test_daftar_layanan_hanya_item_publik(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = $this->verifiedVendor();
        $pending = VendorProfile::factory()->menunggu()->create();

        $visible = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $verified->id]);
        CatalogItem::factory()->create(['vendor_profile_id' => $pending->id]);
        $trashed = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        $trashed->delete();

        $this->actingAs($klien)->get('/klien/katalog-vendor')
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$visible->id]);
    }

    public function test_daftar_peralatan_terpisah_dari_layanan(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = $this->verifiedVendor();
        CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        $peralatan = CatalogItem::factory()->peralatan()->create(['vendor_profile_id' => $verified->id]);

        $this->actingAs($klien)->get('/klien/katalog-peralatan')
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$peralatan->id]);
    }

    public function test_filter_nama_kategori_harga_berjalan_di_server(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = $this->verifiedVendor();
        $murah = CatalogItem::factory()->create([
            'vendor_profile_id' => $verified->id,
            'name' => 'Katering hemat',
            'category' => 'Katering & F&B',
            'catalog_price' => 1_000_000,
        ]);
        CatalogItem::factory()->create([
            'vendor_profile_id' => $verified->id,
            'name' => 'Dekorasi premium',
            'category' => 'Dekorasi',
            'catalog_price' => 20_000_000,
        ]);

        $this->actingAs($klien)->get('/klien/katalog-vendor?q=katering')
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$murah->id]);
        $this->actingAs($klien)->get('/klien/katalog-vendor?category=Dekorasi')
            ->assertViewHas('items', fn ($items) => $items->count() === 1 && $items->first()->name === 'Dekorasi premium');
        $this->actingAs($klien)->get('/klien/katalog-vendor?max_price=5000000')
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$murah->id]);
    }

    // ---- Detail item ----

    public function test_detail_item_publik_dapat_diakses_klien(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = $this->verifiedVendor();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        $public = Media::factory()->publik()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);
        Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]); // privat

        $this->actingAs($klien)->get("/klien/katalog-vendor-detail/{$item->id}")
            ->assertOk()
            ->assertViewHas('photos', fn ($photos) => $photos->pluck('id')->all() === [$public->id]);
    }

    public function test_detail_item_tidak_publik_menghasilkan_404(): void
    {
        $klien = User::factory()->klien()->create();
        $pending = VendorProfile::factory()->menunggu()->create();
        $verified = $this->verifiedVendor();

        $unverified = CatalogItem::factory()->create(['vendor_profile_id' => $pending->id]);
        $inactive = CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $verified->id]);
        $trashed = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        $trashed->delete();

        foreach ([$unverified->id, $inactive->id, $trashed->id, 999_999] as $id) {
            $this->actingAs($klien)->get("/klien/katalog-vendor-detail/{$id}")->assertNotFound();
        }
    }

    public function test_kontak_privat_penyedia_tidak_bocor_di_detail(): void
    {
        $klien = User::factory()->klien()->create();
        $vendorUser = User::factory()->vendor()->create(['phone' => '081234567890']);
        $vendorUser->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendorUser->vendorProfile->id]);

        $response = $this->actingAs($klien)->get("/klien/katalog-vendor-detail/{$item->id}")->assertOk();

        $response->assertDontSee('081234567890');
        $response->assertDontSee($vendorUser->email);
    }

    // ---- Venue ----

    public function test_daftar_venue_hanya_yang_tayang(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = VenueProfile::factory()->terverifikasi()->create();
        $pending = VenueProfile::factory()->menunggu()->create();

        $visible = Venue::factory()->terverifikasi()->create(['venue_profile_id' => $verified->id]);
        Venue::factory()->create(['venue_profile_id' => $verified->id]); // menunggu
        Venue::factory()->terverifikasi()->create([
            'venue_profile_id' => $verified->id,
            'verification_status' => VerificationStatus::Nonaktif,
        ]);
        Venue::factory()->terverifikasi()->create(['venue_profile_id' => $pending->id]);

        $this->actingAs($klien)->get('/klien/katalog')
            ->assertOk()
            ->assertViewHas('venues', fn ($venues) => $venues->pluck('id')->all() === [$visible->id]);
    }

    public function test_detail_venue_publik_memuat_ruangan_dan_fasilitas(): void
    {
        $klien = User::factory()->klien()->create();
        $venue = Venue::factory()->terverifikasi()->create();
        $venue->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        $this->actingAs($klien)->get("/klien/katalog-venue-detail/{$venue->id}")
            ->assertOk()
            ->assertViewHas('venue', fn ($v) => $v->relationLoaded('rooms') && $v->relationLoaded('facilities'));
    }

    public function test_detail_venue_tidak_publik_menghasilkan_404(): void
    {
        $klien = User::factory()->klien()->create();
        $venue = Venue::factory()->create(); // menunggu

        $this->actingAs($klien)->get("/klien/katalog-venue-detail/{$venue->id}")->assertNotFound();

        $venue->update(['verification_status' => VerificationStatus::Nonaktif]);
        $venue->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $this->actingAs($klien)->get("/klien/katalog-venue-detail/{$venue->id}")->assertNotFound();
    }

    // ---- Profil publik penyedia ----

    public function test_profil_vendor_terverifikasi_tampil_dengan_item_publik(): void
    {
        $klien = User::factory()->klien()->create();
        $verified = $this->verifiedVendor();
        $active = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $verified->id]);

        $this->actingAs($klien)->get("/klien/penyedia-vendor/{$verified->id}")
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$active->id]);
    }

    public function test_profil_penyedia_belum_terverifikasi_menghasilkan_404(): void
    {
        $klien = User::factory()->klien()->create();
        $pendingVendor = VendorProfile::factory()->menunggu()->create();
        $pendingVenue = VenueProfile::factory()->menunggu()->create();

        $this->actingAs($klien)->get("/klien/penyedia-vendor/{$pendingVendor->id}")->assertNotFound();
        $this->actingAs($klien)->get("/klien/penyedia-venue/{$pendingVenue->id}")->assertNotFound();
    }

    public function test_profil_venue_terverifikasi_tampil_dengan_venue_tayang(): void
    {
        $klien = User::factory()->klien()->create();
        $profile = VenueProfile::factory()->terverifikasi()->create();
        $visible = Venue::factory()->terverifikasi()->create(['venue_profile_id' => $profile->id]);
        Venue::factory()->create(['venue_profile_id' => $profile->id]);

        $this->actingAs($klien)->get("/klien/penyedia-venue/{$profile->id}")
            ->assertOk()
            ->assertViewHas('venues', fn ($venues) => $venues->pluck('id')->all() === [$visible->id]);
    }

    // ---- Otorisasi portal ----

    public function test_role_lain_ditolak_masuk_katalog_klien(): void
    {
        $item = CatalogItem::factory()->create();

        foreach ([User::factory()->vendor()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/klien/katalog-vendor')->assertForbidden();
            $this->actingAs($user)->get("/klien/katalog-vendor-detail/{$item->id}")->assertForbidden();
        }
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $this->get('/klien/katalog-vendor')->assertRedirect('/login');
        $this->get('/klien/katalog')->assertRedirect('/login');
    }
}
