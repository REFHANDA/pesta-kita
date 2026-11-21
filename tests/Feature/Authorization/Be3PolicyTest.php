<?php

namespace Tests\Feature\Authorization;

use App\Enums\VerificationDocType;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * BE-3 langkah 2: matriks policy — kepemilikan lintas penyedia, role salah,
 * IDOR, dan aturan baca media B12 (dokumen privat selamanya; foto publik
 * hanya bila penyedia terverifikasi dan subjek tayang).
 */
class Be3PolicyTest extends TestCase
{
    use RefreshDatabase;

    // ---- CatalogItemPolicy ----

    public function test_pemilik_item_boleh_kelola_itemnya(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);

        foreach (['view', 'update', 'toggleStatus', 'managePhotos'] as $ability) {
            $this->assertTrue(Gate::forUser($vendor)->allows($ability, $item), "Ability {$ability} seharusnya boleh.");
        }
        // Hapus item ditiadakan; penyedia memakai nonaktifkan.
        $this->assertFalse(Gate::forUser($vendor)->allows('delete', $item));
        $this->assertTrue(Gate::forUser($vendor)->allows('create', CatalogItem::class));
    }

    public function test_vendor_lain_ditolak_untuk_item_bukan_miliknya(): void
    {
        $owner = User::factory()->vendor()->create();
        $intruder = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $owner->vendorProfile->id]);

        foreach (['view', 'update', 'delete', 'toggleStatus', 'managePhotos'] as $ability) {
            $this->assertFalse(Gate::forUser($intruder)->allows($ability, $item), "Ability {$ability} seharusnya ditolak (IDOR).");
        }
    }

    public function test_role_non_vendor_ditolak_untuk_kelola_item(): void
    {
        $owner = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $owner->vendorProfile->id]);

        foreach ([User::factory()->klien()->create(), User::factory()->venue()->create()] as $user) {
            foreach (['update', 'delete', 'toggleStatus', 'managePhotos'] as $ability) {
                $this->assertFalse(Gate::forUser($user)->allows($ability, $item));
            }
        }
        $this->assertFalse(Gate::forUser(User::factory()->klien()->create())->allows('create', CatalogItem::class));
    }

    public function test_admin_boleh_melihat_tapi_tidak_mengubah_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = CatalogItem::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $item));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $item));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $item));
    }

    // ---- VenuePolicy & VenueRoomPolicy ----

    public function test_pemilik_venue_boleh_kelola_venue_dan_ruangan(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        foreach (['view', 'update', 'manageRooms', 'managePhotos'] as $ability) {
            $this->assertTrue(Gate::forUser($owner)->allows($ability, $venue));
        }
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $venue));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $room));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $room));
        $this->assertTrue(Gate::forUser($owner)->allows('create', Venue::class));
    }

    public function test_penyedia_lain_ditolak_untuk_venue_dan_ruangan(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $room = VenueRoom::factory()->create(['venue_id' => $venue->id]);

        foreach ([User::factory()->venue()->create(), User::factory()->vendor()->create(), User::factory()->klien()->create()] as $intruder) {
            $this->assertFalse(Gate::forUser($intruder)->allows('update', $venue));
            $this->assertFalse(Gate::forUser($intruder)->allows('delete', $venue));
            $this->assertFalse(Gate::forUser($intruder)->allows('update', $room));
            $this->assertFalse(Gate::forUser($intruder)->allows('delete', $room));
        }
    }

    public function test_hanya_admin_yang_boleh_admin_manage_venue(): void
    {
        $owner = User::factory()->venue()->create();
        $venue = Venue::factory()->create(['venue_profile_id' => $owner->venueProfile->id]);

        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('adminManage', $venue));
        $this->assertFalse(Gate::forUser($owner)->allows('adminManage', $venue));
        $this->assertFalse(Gate::forUser(User::factory()->klien()->create())->allows('adminManage', $venue));
    }

    // ---- MediaPolicy: baca ----

    public function test_pemilik_dan_admin_boleh_membaca_dokumen_verifikasi(): void
    {
        $vendor = User::factory()->vendor()->create();
        $doc = Media::factory()->dokumen(VerificationDocType::Ktp)->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
        ]);

        $this->assertTrue(Gate::forUser($vendor)->allows('view', $doc));
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('view', $doc));
    }

    public function test_dokumen_verifikasi_ditolak_untuk_pihak_lain_meski_publik(): void
    {
        $vendor = User::factory()->vendor()->create();
        $doc = Media::factory()->dokumen()->publik()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
        ]);

        foreach ([User::factory()->klien()->create(), User::factory()->vendor()->create(), User::factory()->venue()->create()] as $other) {
            $this->assertFalse(Gate::forUser($other)->allows('view', $doc), 'Dokumen tidak boleh bocor (B12).');
        }
    }

    public function test_foto_privat_ditolak_untuk_klien(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->assertFalse(Gate::forUser(User::factory()->klien()->create())->allows('view', $photo));
        $this->assertTrue(Gate::forUser($vendor)->allows('view', $photo));
    }

    public function test_foto_publik_item_aktif_penyedia_terverifikasi_boleh_untuk_klien(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->publik()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->assertTrue(Gate::forUser(User::factory()->klien()->create())->allows('view', $photo));
    }

    public function test_foto_publik_ditolak_bila_penyedia_belum_terverifikasi(): void
    {
        $vendor = User::factory()->vendor()->create(); // menunggu
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->publik()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->assertFalse(Gate::forUser(User::factory()->klien()->create())->allows('view', $photo));
    }

    public function test_foto_publik_ditolak_bila_item_nonaktif_atau_soft_deleted(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $klien = User::factory()->klien()->create();

        $inactive = CatalogItem::factory()->nonaktif()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photoInactive = Media::factory()->publik()->create(['owner_type' => 'catalog_item', 'owner_id' => $inactive->id]);
        $this->assertFalse(Gate::forUser($klien)->allows('view', $photoInactive));

        $trashed = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photoTrashed = Media::factory()->publik()->create(['owner_type' => 'catalog_item', 'owner_id' => $trashed->id]);
        $trashed->delete();
        $this->assertFalse(Gate::forUser($klien)->allows('view', $photoTrashed->fresh()));
    }

    public function test_foto_publik_venue_dinonaktifkan_admin_ditolak_untuk_klien(): void
    {
        $owner = User::factory()->venue()->create();
        $owner->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $venue = Venue::factory()->terverifikasi()->create(['venue_profile_id' => $owner->venueProfile->id]);
        $photo = Media::factory()->publik()->create(['owner_type' => 'venue', 'owner_id' => $venue->id]);
        $klien = User::factory()->klien()->create();

        $this->assertTrue(Gate::forUser($klien)->allows('view', $photo));

        // Keputusan Gate 4B butir 3: penonaktifan individual oleh Admin.
        $venue->update(['verification_status' => VerificationStatus::Nonaktif]);
        $this->assertFalse(Gate::forUser($klien)->allows('view', $photo->fresh()));
    }

    // ---- MediaPolicy: hapus ----

    public function test_hapus_media_hanya_pemilik_atau_admin(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $vendor->vendorProfile->id]);
        $photo = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);

        $this->assertTrue(Gate::forUser($vendor)->allows('delete', $photo));
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('delete', $photo));
        $this->assertFalse(Gate::forUser(User::factory()->vendor()->create())->allows('delete', $photo));
        $this->assertFalse(Gate::forUser(User::factory()->klien()->create())->allows('delete', $photo));
    }

    // ---- ProviderProfilePolicy ----

    public function test_review_dan_decide_hanya_admin(): void
    {
        $profile = User::factory()->vendor()->create()->vendorProfile;

        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('review', $profile));
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('decide', $profile));

        foreach ([User::factory()->vendor()->create(), User::factory()->venue()->create(), User::factory()->klien()->create()] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('review', $profile));
            $this->assertFalse(Gate::forUser($user)->allows('decide', $profile));
        }
    }

    public function test_submit_dan_upload_dokumen_hanya_pemilik_profil(): void
    {
        $owner = User::factory()->venue()->create();
        $profile = $owner->venueProfile;

        $this->assertTrue(Gate::forUser($owner)->allows('submit', $profile));
        $this->assertTrue(Gate::forUser($owner)->allows('uploadDocument', $profile));

        foreach ([User::factory()->venue()->create(), User::factory()->vendor()->create(), User::factory()->klien()->create(), User::factory()->admin()->create()] as $other) {
            $this->assertFalse(Gate::forUser($other)->allows('submit', $profile));
            $this->assertFalse(Gate::forUser($other)->allows('uploadDocument', $profile));
        }
    }

    // ---- Disk private ----

    public function test_disk_private_ada_dan_tidak_serve(): void
    {
        $config = config('filesystems.disks.private');

        $this->assertNotNull($config);
        $this->assertSame('local', $config['driver']);
        $this->assertFalse($config['serve']);
        $this->assertSame(storage_path('app/private'), $config['root']);
    }
}
