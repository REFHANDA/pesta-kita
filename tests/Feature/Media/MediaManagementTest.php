<?php

namespace Tests\Feature\Media;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationDocType;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

/**
 * BE-3 langkah 3 (B12/B14): unggah foto/dokumen, validasi MIME & ukuran
 * sisi server, batas 10 foto, satu dokumen per jenis, baca berotorisasi,
 * hapus aman, dan penutupan jalur storage publik.
 */
class MediaManagementTest extends TestCase
{
    use MakesUploadedFiles, RefreshDatabase;

    protected FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake('private');
    }

    // ---- Unggah foto ----

    public function test_vendor_mengunggah_foto_item_miliknya(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $response = $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => $this->imageFile('foto.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $media = $item->media()->first();
        $this->assertNotNull($media);
        $this->assertSame(MediaKind::Foto, $media->kind);
        $this->assertSame('private', $media->disk);
        $this->assertSame(MediaVisibility::Privat, $media->visibility, 'B12: belum verifikasi harus privat.');
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame('foto.jpg', $media->original_name);
        $this->assertGreaterThan(0, $media->size);
        $this->assertNotSame('foto.jpg', basename($media->path), 'Nama file harus acak.');
        $this->disk->assertExists($media->path);
    }

    public function test_foto_penyedia_terverifikasi_langsung_publik(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => $this->imageFile('foto.png'),
        ]);

        $this->assertSame(MediaVisibility::Publik, $item->media()->first()->visibility);
    }

    public function test_batas_sepuluh_foto_per_item(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        Media::factory()->count(10)->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $response = $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => $this->imageFile('foto.jpg'),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertSame(10, $item->media()->foto()->count());
    }

    public function test_format_foto_tidak_diizinkan_ditolak(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $response = $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => UploadedFile::fake()->createWithContent('skrip.php', '<?php echo "halo";'),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertSame(0, $item->media()->count());
    }

    public function test_foto_lebih_dari_5mb_ditolak(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        $response = $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => UploadedFile::fake()->createWithContent('besar.jpg', str_repeat('x', 6 * 1024 * 1024)),
        ]);

        $response->assertSessionHasErrors('photo');
        $this->assertSame(0, $item->media()->count());
    }

    public function test_vendor_lain_ditolak_mengunggah_foto_item_bukan_miliknya(): void
    {
        $owner = User::factory()->vendor()->create();
        $intruder = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $owner->vendorProfile->id]);

        $this->actingAs($intruder)->post("/vendor/katalog/{$item->id}/foto", [
            'photo' => $this->imageFile(),
        ])->assertForbidden();

        $this->assertSame(0, $item->media()->count());
    }

    public function test_klien_ditolak_di_rute_unggah_vendor(): void
    {
        $item = CatalogItem::factory()->create();

        $this->actingAs(User::factory()->klien()->create())
            ->post("/vendor/katalog/{$item->id}/foto", ['photo' => $this->imageFile()])
            ->assertForbidden();
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $item = CatalogItem::factory()->create();

        $this->post("/vendor/katalog/{$item->id}/foto", ['photo' => $this->imageFile()])
            ->assertRedirect('/login');
    }

    public function test_pemilik_venue_mengunggah_foto_venue(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->actingAs($owner)->post("/venue/data/{$venue->id}/foto", [
            'photo' => $this->imageFile('venue.webp'),
        ])->assertRedirect();

        $media = $venue->media()->first();
        $this->assertSame('image/webp', $media->mime_type);
        $this->disk->assertExists($media->path);
    }

    public function test_penyedia_mengunggah_foto_profil(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/profil/foto', [
            'photo' => $this->imageFile('profil.jpg'),
        ])->assertRedirect();

        $this->assertSame(1, $vendor->vendorProfile->media()->foto()->count());
    }

    public function test_klien_tidak_bisa_mengunggah_foto_profil(): void
    {
        $this->actingAs(User::factory()->klien()->create())
            ->post('/profil/foto', ['photo' => $this->imageFile()])
            ->assertForbidden();
    }

    // ---- Unggah dokumen verifikasi ----

    public function test_vendor_mengunggah_dokumen_ktp_privat(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('ktp.pdf'),
        ])->assertRedirect();

        $media = $vendor->vendorProfile->media()->first();
        $this->assertSame(MediaKind::DokumenVerifikasi, $media->kind);
        $this->assertSame(VerificationDocType::Ktp, $media->doc_type);
        $this->assertSame(MediaVisibility::Privat, $media->visibility);
        $this->assertSame('application/pdf', $media->mime_type);
        $this->disk->assertExists($media->path);
    }

    public function test_upload_ulang_menggantikan_dokumen_lama_jenis_sama(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('ktp-lama.pdf'),
        ]);
        $old = $vendor->vendorProfile->media()->dokumenVerifikasi()->first();

        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('ktp-baru.pdf'),
        ]);

        $docs = $vendor->vendorProfile->media()->dokumenVerifikasi()->where('doc_type', 'ktp')->get();
        $this->assertCount(1, $docs, 'Keputusan butir 10: satu dokumen aktif per jenis.');
        $this->assertNotSame($old->id, $docs->first()->id);
        $this->disk->assertMissing($old->path);
        $this->disk->assertExists($docs->first()->path);
    }

    public function test_jenis_dokumen_harus_sesuai_role(): void
    {
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        // bukti_venue hanya untuk venue; nib hanya untuk vendor.
        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'bukti_venue',
            'file' => $this->pdfFile(),
        ])->assertSessionHasErrors('doc_type');

        $this->actingAs($venue)->post('/verifikasi/dokumen', [
            'doc_type' => 'nib',
            'file' => $this->pdfFile(),
        ])->assertSessionHasErrors('doc_type');

        $this->actingAs($venue)->post('/verifikasi/dokumen', [
            'doc_type' => 'bukti_venue',
            'file' => $this->pdfFile(),
        ])->assertRedirect();
    }

    public function test_dokumen_lebih_dari_5mb_ditolak(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('besar.pdf', 6 * 1024),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, $vendor->vendorProfile->media()->count());
    }

    public function test_klien_ditolak_mengunggah_dokumen_verifikasi(): void
    {
        $this->actingAs(User::factory()->klien()->create())
            ->post('/verifikasi/dokumen', ['doc_type' => 'ktp', 'file' => $this->pdfFile()])
            ->assertForbidden();
    }

    // ---- Baca media (satu pintu berotorisasi) ----

    public function test_pemilik_dan_admin_dapat_membaca_dokumen(): void
    {
        $vendor = User::factory()->vendor()->create();
        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('ktp.pdf'),
        ]);
        $media = $vendor->vendorProfile->media()->first();

        $this->actingAs($vendor)->get("/media/{$media->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs(User::factory()->admin()->create())
            ->get("/media/{$media->id}")->assertOk();
    }

    public function test_klien_ditolak_membaca_dokumen_verifikasi(): void
    {
        $vendor = User::factory()->vendor()->create();
        $doc = Media::factory()->dokumen()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
        ]);

        $this->actingAs(User::factory()->klien()->create())
            ->get("/media/{$doc->id}")->assertForbidden();
    }

    public function test_klien_dapat_membaca_foto_publik_penyedia_terverifikasi(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", ['photo' => $this->imageFile()]);
        $media = $item->media()->first();

        $this->actingAs(User::factory()->klien()->create())
            ->get("/media/{$media->id}")->assertOk();
    }

    public function test_klien_ditolak_membaca_foto_privat(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->actingAs(User::factory()->klien()->create())
            ->get("/media/{$photo->id}")->assertForbidden();
    }

    public function test_media_tanpa_file_fisik_menghasilkan_404(): void
    {
        $vendor = User::factory()->vendor()->create();
        $media = Media::factory()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
        ]);

        $this->actingAs($vendor)->get("/media/{$media->id}")->assertNotFound();
    }

    // ---- Hapus media ----

    public function test_pemilik_menghapus_media_catatan_dan_file(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $this->actingAs($vendor)->post("/vendor/katalog/{$item->id}/foto", ['photo' => $this->imageFile()]);
        $media = $item->media()->first();

        $this->actingAs($vendor)->delete("/media/{$media->id}")->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->disk->assertMissing($media->path);
    }

    public function test_pihak_lain_ditolak_menghapus_media(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->actingAs(User::factory()->vendor()->create())->delete("/media/{$photo->id}")->assertForbidden();
        $this->actingAs(User::factory()->klien()->create())->delete("/media/{$photo->id}")->assertForbidden();
        $this->assertDatabaseHas('media', ['id' => $photo->id]);
    }

    // ---- Jalur storage publik ditutup ----

    public function test_rute_storage_framework_tidak_melayani_file(): void
    {
        $vendor = User::factory()->vendor()->create();
        $this->actingAs($vendor)->post('/verifikasi/dokumen', [
            'doc_type' => 'ktp',
            'file' => $this->pdfFile('ktp.pdf'),
        ]);
        $media = $vendor->vendorProfile->media()->first();

        // Bahkan tanpa login, path internal tidak boleh dapat diakses.
        $this->get('/storage/'.$media->path)->assertNotFound();
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertFalse(config('filesystems.disks.private.serve'));
    }
}
