<?php

namespace Tests\Feature\Database;

use App\Enums\CatalogItemStatus;
use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationDocType;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * BE-3 langkah 1: migration additive media (kind/doc_type/disk/metadata)
 * dan kolom review verifikasi profil, tanpa mengubah skema BE-1/BE-2.
 * Termasuk scope publikUntukKlien dan resolusi providerProfile media.
 */
class Be3SchemaAdditionsTest extends TestCase
{
    use RefreshDatabase;

    // ---- Kolom baru media ----

    public function test_media_memiliki_kolom_baru_b12(): void
    {
        foreach (['kind', 'doc_type', 'disk', 'mime_type', 'size', 'original_name'] as $column) {
            $this->assertTrue(Schema::hasColumn('media', $column), "Kolom media.{$column} tidak ada.");
        }
    }

    public function test_media_default_foto_private_privat(): void
    {
        $media = Media::factory()->create();

        $this->assertSame(MediaKind::Foto, $media->kind);
        $this->assertNull($media->doc_type);
        $this->assertSame('private', $media->disk);
        $this->assertSame(MediaVisibility::Privat, $media->visibility);
    }

    public function test_media_dokumen_verifikasi_memiliki_doc_type_dan_privat(): void
    {
        $media = Media::factory()->dokumen(VerificationDocType::Ktp)->create();

        $this->assertSame(MediaKind::DokumenVerifikasi, $media->kind);
        $this->assertSame(VerificationDocType::Ktp, $media->doc_type);
        $this->assertSame(MediaVisibility::Privat, $media->visibility);
    }

    public function test_scope_media_memisahkan_foto_dan_dokumen(): void
    {
        $item = CatalogItem::factory()->create();
        Media::factory()->count(2)->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);
        Media::factory()->dokumen()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->assertSame(2, $item->media()->foto()->count());
        $this->assertSame(1, $item->media()->dokumenVerifikasi()->count());
    }

    // ---- Kolom review verifikasi profil ----

    public function test_profil_memiliki_kolom_review_verifikasi(): void
    {
        foreach (['vendor_profiles', 'venue_profiles'] as $table) {
            foreach (['verification_note', 'reviewed_by', 'verification_submitted_at'] as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column), "Kolom {$table}.{$column} tidak ada.");
            }
        }
    }

    public function test_kolom_review_nullable_dan_default_kosong(): void
    {
        $vendor = VendorProfile::factory()->create();
        $venue = VenueProfile::factory()->create();

        foreach ([$vendor, $venue] as $profile) {
            $this->assertNull($profile->verification_note);
            $this->assertNull($profile->reviewed_by);
            $this->assertNull($profile->verification_submitted_at);
            $this->assertSame(VerificationStatus::Menunggu, $profile->verification_status);
        }
    }

    public function test_reviewer_relasi_dan_cast_datetime(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = VendorProfile::factory()->create([
            'verification_note' => 'Dokumen KTP buram.',
            'reviewed_by' => $admin->id,
            'verification_submitted_at' => now(),
        ]);

        $this->assertTrue($profile->reviewer->is($admin));
        $this->assertInstanceOf(Carbon::class, $profile->verification_submitted_at);
        $this->assertSame('Dokumen KTP buram.', $profile->verification_note);
    }

    // ---- Resolusi providerProfile media ----

    public function test_provider_profile_media_untuk_semua_jenis_owner(): void
    {
        $vendorProfile = VendorProfile::factory()->create();
        $venueProfile = VenueProfile::factory()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendorProfile->id]);
        $venue = Venue::factory()->create(['venue_profile_id' => $venueProfile->id]);

        $cases = [
            ['vendor_profile', $vendorProfile->id, $vendorProfile],
            ['venue_profile', $venueProfile->id, $venueProfile],
            ['catalog_item', $item->id, $vendorProfile],
            ['venue', $venue->id, $venueProfile],
        ];

        foreach ($cases as [$ownerType, $ownerId, $expected]) {
            $media = Media::factory()->create(['owner_type' => $ownerType, 'owner_id' => $ownerId]);
            $this->assertTrue($media->providerProfile()->is($expected), "Resolusi gagal untuk {$ownerType}.");
        }
    }

    public function test_provider_profile_null_untuk_owner_soft_deleted(): void
    {
        $item = CatalogItem::factory()->create();
        $media = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);
        $item->delete();

        $this->assertNull($media->fresh()->providerProfile());
    }

    // ---- Scope publikUntukKlien ----

    public function test_scope_item_publik_hanya_aktif_dan_penyedia_terverifikasi(): void
    {
        $verified = VendorProfile::factory()->terverifikasi()->create();
        $pending = VendorProfile::factory()->menunggu()->create();

        $visible = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $verified->id]);
        CatalogItem::factory()->create(['vendor_profile_id' => $pending->id]);
        $trashed = CatalogItem::factory()->create(['vendor_profile_id' => $verified->id]);
        $trashed->delete();

        $ids = CatalogItem::publikUntukKlien()->pluck('id')->all();
        $this->assertSame([$visible->id], $ids);
    }

    public function test_scope_venue_publik_mengikuti_profil_dan_status_venue(): void
    {
        $verified = VenueProfile::factory()->terverifikasi()->create();
        $pending = VenueProfile::factory()->menunggu()->create();

        $visible = Venue::factory()->terverifikasi()->create(['venue_profile_id' => $verified->id]);
        // Keputusan Gate 4B butir 3: Admin dapat menonaktifkan venue individual.
        Venue::factory()->create([
            'venue_profile_id' => $verified->id,
            'verification_status' => VerificationStatus::Nonaktif,
        ]);
        Venue::factory()->terverifikasi()->create(['venue_profile_id' => $pending->id]);
        $trashed = Venue::factory()->terverifikasi()->create(['venue_profile_id' => $verified->id]);
        $trashed->delete();

        $ids = Venue::publikUntukKlien()->pluck('id')->all();
        $this->assertSame([$visible->id], $ids);
    }

    public function test_item_nonaktif_tetap_terlihat_oleh_pemilik_tanpa_scope_publik(): void
    {
        $profile = VendorProfile::factory()->terverifikasi()->create();
        CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $profile->id]);

        // Daftar penyedia memakai relasi langsung (tanpa scope publik).
        $this->assertSame(1, $profile->catalogItems()->count());
        $this->assertSame(CatalogItemStatus::Nonaktif, $profile->catalogItems()->first()->status);
    }
}
