<?php

namespace Tests\Feature\Vendor;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\PricingMode;
use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * BE-3 langkah 4: CRUD katalog vendor — kepemilikan dari akun login,
 * whitelist field (anti penyelundupan vendor_profile_id/status), harga
 * integer rupiah, toggle aktif/nonaktif, soft delete, dan penolakan
 * lintas penyedia/role.
 */
class CatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload dasar item layanan yang valid.
     *
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'kind' => 'layanan',
            'name' => 'Katering prasmanan',
            'category' => 'Katering & F&B',
            'description' => 'Prasmanan 8 menu.',
            'catalog_price' => 2_720_000,
            'pricing_mode' => 'tetap',
            'max_per_day' => 2,
            'policies' => 'DP minimal 50%.',
        ], $overrides);
    }

    // ---- Create ----

    public function test_vendor_membuat_layanan_milik_sendiri_dan_langsung_aktif(): void
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)->post('/vendor/katalog', $this->payload());

        $response->assertRedirect(route('vendor.layanan'));
        $item = CatalogItem::first();
        $this->assertSame($vendor->vendorProfile->id, $item->vendor_profile_id);
        $this->assertSame(CatalogKind::Layanan, $item->kind);
        $this->assertSame(CatalogItemStatus::Aktif, $item->status, 'Keputusan butir 2: tanpa persetujuan per item.');
        $this->assertSame(2_720_000, $item->catalog_price);
        $this->assertSame(PricingMode::Tetap, $item->pricing_mode);
    }

    public function test_vendor_membuat_peralatan_dengan_kuota(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/vendor/katalog', $this->payload([
            'kind' => 'peralatan',
            'name' => 'Kursi Tiffany',
            'category' => 'Peralatan Pesta',
            'max_per_day' => 200,
        ]))->assertRedirect(route('vendor.peralatan'));

        $this->assertSame(CatalogKind::Peralatan, CatalogItem::first()->kind);
    }

    public function test_field_kepemilikan_dan_status_tidak_dapat_diselundupkan(): void
    {
        $vendor = User::factory()->vendor()->create();
        $otherProfile = User::factory()->vendor()->create()->vendorProfile;

        $this->actingAs($vendor)->post('/vendor/katalog', $this->payload([
            'vendor_profile_id' => $otherProfile->id,
            'status' => 'nonaktif',
            'verification_status' => 'terverifikasi',
        ]));

        $item = CatalogItem::first();
        $this->assertSame($vendor->vendorProfile->id, $item->vendor_profile_id);
        $this->assertSame(CatalogItemStatus::Aktif, $item->status);
    }

    public function test_validasi_gagal_menolak_payload_tidak_lengkap(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/vendor/katalog', [])
            ->assertSessionHasErrors(['kind', 'name', 'category', 'catalog_price', 'pricing_mode']);

        $this->actingAs($vendor)->post('/vendor/katalog', $this->payload([
            'catalog_price' => -100,
            'kind' => 'invalid',
            'max_per_day' => 0,
        ]))->assertSessionHasErrors(['catalog_price', 'kind', 'max_per_day']);

        $this->assertSame(0, CatalogItem::count());
    }

    public function test_harga_harus_integer_rupiah(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/vendor/katalog', $this->payload([
            'catalog_price' => '2720000.50',
        ]))->assertSessionHasErrors('catalog_price');
    }

    public function test_role_lain_ditolak_membuat_item(): void
    {
        foreach ([User::factory()->klien()->create(), User::factory()->venue()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->post('/vendor/katalog', $this->payload())->assertForbidden();
        }
        $this->assertSame(0, CatalogItem::count());
    }

    // ---- Read (daftar & form) ----

    public function test_halaman_daftar_memuat_item_milik_sendiri_per_kind(): void
    {
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile;
        $layanan = CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);
        $peralatan = CatalogItem::factory()->peralatan()->create(['vendor_profile_id' => $profile->id]);
        $nonaktif = CatalogItem::factory()->peralatan()->nonaktif()->create(['vendor_profile_id' => $profile->id]);
        // Item vendor lain tidak boleh ikut.
        CatalogItem::factory()->create();

        $this->actingAs($vendor)->get('/vendor/layanan')
            ->assertOk()->assertViewHas('items', fn ($items) => $items->pluck('id')->all() === [$layanan->id]);
        // Daftar penyedia tetap memuat item nonaktif (milik sendiri).
        $this->actingAs($vendor)->get('/vendor/peralatan')
            ->assertOk()->assertViewHas('items', fn ($items) => $items->pluck('id')->sort()->values()->all() === collect([$peralatan->id, $nonaktif->id])->sort()->values()->all());
    }

    public function test_form_tambah_dan_ubah_dapat_diakses_pemilik(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->get('/vendor/item-form')->assertOk()->assertViewHas('item', null);
        $this->actingAs($vendor)->get('/vendor/peralatan-form')->assertOk();
        $this->actingAs($vendor)->get("/vendor/item-form/{$item->id}")->assertOk()->assertViewHas('item');
    }

    public function test_form_ubah_item_vendor_lain_ditolak(): void
    {
        $intruder = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create();

        $this->actingAs($intruder)->get("/vendor/item-form/{$item->id}")->assertForbidden();
    }

    // ---- Update ----

    public function test_vendor_memperbarui_item_miliknya(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->put("/vendor/katalog/{$item->id}", $this->payload([
            'name' => 'Katering premium',
            'catalog_price' => 3_000_000,
        ]))->assertRedirect();

        $item->refresh();
        $this->assertSame('Katering premium', $item->name);
        $this->assertSame(3_000_000, $item->catalog_price);
        $this->assertSame(CatalogKind::Layanan, $item->kind, 'Kind tidak berubah lewat update.');
    }

    public function test_update_tidak_mengubah_kepemilikan_atau_status(): void
    {
        $vendor = User::factory()->vendor()->create();
        $otherProfile = User::factory()->vendor()->create()->vendorProfile;
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->put("/vendor/katalog/{$item->id}", $this->payload([
            'vendor_profile_id' => $otherProfile->id,
            'status' => 'nonaktif',
            'kind' => 'peralatan',
        ]));

        $item->refresh();
        $this->assertSame($vendor->vendorProfile->id, $item->vendor_profile_id);
        $this->assertSame(CatalogItemStatus::Aktif, $item->status);
        $this->assertSame(CatalogKind::Layanan, $item->kind);
    }

    public function test_vendor_lain_ditolak_memperbarui_item(): void
    {
        $intruder = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create();

        $this->actingAs($intruder)->put("/vendor/katalog/{$item->id}", $this->payload())->assertForbidden();
    }

    // ---- Toggle status ----

    public function test_pemilik_mengaktifkan_dan_menonaktifkan_item(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/status", ['status' => 'nonaktif']);
        $this->assertSame(CatalogItemStatus::Nonaktif, $item->fresh()->status);

        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/status", ['status' => 'aktif']);
        $this->assertSame(CatalogItemStatus::Aktif, $item->fresh()->status);
    }

    public function test_status_tidak_valid_ditolak(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/status", ['status' => 'menunggu_persetujuan'])
            ->assertSessionHasErrors('status');
        $this->assertSame(CatalogItemStatus::Aktif, $item->fresh()->status);
    }

    public function test_vendor_lain_ditolak_mengubah_status_item(): void
    {
        $intruder = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create();

        $this->actingAs($intruder)->post("/vendor/katalog/{$item->id}/status", ['status' => 'nonaktif'])
            ->assertForbidden();
    }

    // ---- Hapus ditiadakan (pakai nonaktifkan) ----

    public function test_route_hapus_item_tidak_ada_lagi(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->assertFalse(Route::has('vendor.katalog.destroy'));
        $this->actingAs($vendor)->delete("/vendor/katalog/{$item->id}")->assertMethodNotAllowed();
        $this->assertDatabaseHas('catalog_items', ['id' => $item->id, 'deleted_at' => null]);

        $this->actingAs($vendor)->get('/vendor/layanan')->assertOk()->assertDontSee('>Hapus<', false);
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $this->post('/vendor/katalog', $this->payload())->assertRedirect('/login');
        $this->get('/vendor/layanan')->assertRedirect('/login');
    }
}
