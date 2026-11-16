<?php

namespace Tests\Feature\Verification;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\UserStatus;
use App\Enums\VerificationDocType;
use App\Enums\VerificationStatus;
use App\Models\CatalogItem;
use App\Models\Media;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * BE-3 langkah 6 (B14): state machine verifikasi penyedia — pengajuan
 * dengan prasyarat dokumen, approve (foto publik + venue mengikuti),
 * tolak beralasan, ajukan ulang, pencatatan reviewer/audit, dan T2
 * (status akun tidak pernah tersentuh keputusan verifikasi).
 */
class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Upload dokumen lewat endpoint nyata agar prasyarat terpenuhi.
     */
    protected function uploadDoc(User $user, string $docType): void
    {
        $this->actingAs($user)->post('/verifikasi/dokumen', [
            'doc_type' => $docType,
            'file' => UploadedFile::fake()->createWithContent($docType.'.pdf', "%PDF-1.4\n%%EOF"),
        ]);
    }

    // ---- Pengajuan ----

    public function test_pengajuan_ditolak_bila_dokumen_wajib_belum_lengkap(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->post('/verifikasi/ajukan')
            ->assertSessionHasErrors('verification');
        $this->assertSame(VerificationStatus::Menunggu, $vendor->vendorProfile->fresh()->verification_status);
        $this->assertNull($vendor->vendorProfile->fresh()->verification_submitted_at);
    }

    public function test_vendor_mengajukan_verifikasi_setelah_ktp_diunggah(): void
    {
        $vendor = User::factory()->vendor()->create();
        $this->uploadDoc($vendor, 'ktp');

        $this->actingAs($vendor)->post('/verifikasi/ajukan')->assertRedirect();

        $profile = $vendor->vendorProfile->fresh();
        $this->assertSame(VerificationStatus::Menunggu, $profile->verification_status);
        $this->assertNotNull($profile->verification_submitted_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $vendor->id,
            'action' => 'verification_submitted',
            'subject_type' => 'vendor_profile',
            'subject_id' => $profile->id,
        ]);
    }

    public function test_venue_wajib_ktp_dan_bukti_venue(): void
    {
        $venue = User::factory()->venue()->create();
        $this->uploadDoc($venue, 'ktp');

        $this->actingAs($venue)->post('/verifikasi/ajukan')
            ->assertSessionHasErrors('verification');

        $this->uploadDoc($venue, 'bukti_venue');
        $this->actingAs($venue)->post('/verifikasi/ajukan')->assertRedirect();
        $this->assertNotNull($venue->venueProfile->fresh()->verification_submitted_at);
    }

    public function test_penyedia_terverifikasi_tidak_dapat_mengajukan_lagi(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->vendorProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);
        $this->uploadDoc($vendor, 'ktp');

        $this->actingAs($vendor)->post('/verifikasi/ajukan')
            ->assertSessionHasErrors('verification');
    }

    public function test_halaman_verifikasi_menampilkan_profil_dan_dokumen(): void
    {
        $vendor = User::factory()->vendor()->create();
        $this->uploadDoc($vendor, 'ktp');

        $this->actingAs($vendor)->get('/vendor/verifikasi')
            ->assertOk()
            ->assertViewHas('profile')
            ->assertViewHas('documents', fn ($docs) => $docs->has('ktp'));

        $venue = User::factory()->venue()->create();
        $this->actingAs($venue)->get('/venue/verifikasi')->assertOk()->assertViewHas('requiredTypes');
    }

    // ---- Approve ----

    public function test_admin_menyetujui_verifikasi_vendor_dan_mempublikasikan_foto(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile;
        $item = CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);

        $profilePhoto = Media::factory()->create(['owner_type' => 'vendor_profile', 'owner_id' => $profile->id]);
        $itemPhoto = Media::factory()->create(['owner_type' => 'catalog_item', 'owner_id' => $item->id]);
        $doc = Media::factory()->dokumen(VerificationDocType::Ktp)->create(['owner_type' => 'vendor_profile', 'owner_id' => $profile->id]);

        $this->actingAs($admin)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/setujui")
            ->assertRedirect(route('admin.penyedia.index'));

        $profile->refresh();
        $this->assertSame(VerificationStatus::Terverifikasi, $profile->verification_status);
        $this->assertNotNull($profile->verified_at);
        $this->assertSame($admin->id, $profile->reviewed_by);

        // B12: foto publik, dokumen tetap privat selamanya.
        $this->assertSame(MediaVisibility::Publik, $profilePhoto->fresh()->visibility);
        $this->assertSame(MediaVisibility::Publik, $itemPhoto->fresh()->visibility);
        $this->assertSame(MediaVisibility::Privat, $doc->fresh()->visibility);
        $this->assertSame(MediaKind::DokumenVerifikasi, $doc->fresh()->kind);

        // T2: akun tetap aktif, tidak tersentuh keputusan verifikasi.
        $this->assertSame(UserStatus::Aktif, $vendor->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'verification_approved',
            'subject_type' => 'vendor_profile',
            'subject_id' => $profile->id,
            'from_status' => 'menunggu',
            'to_status' => 'terverifikasi',
        ]);
    }

    public function test_approve_venue_mengikuti_profil_kecuali_yang_dinonaktifkan_individual(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->venue()->create();
        $profile = $owner->venueProfile;

        $venue = Venue::factory()->create(['venue_profile_id' => $profile->id]);
        $disabled = Venue::factory()->create([
            'venue_profile_id' => $profile->id,
            'verification_status' => VerificationStatus::Nonaktif,
        ]);
        $venuePhoto = Media::factory()->create(['owner_type' => 'venue', 'owner_id' => $venue->id]);

        $this->actingAs($admin)->post("/admin/penyedia/venue_profile/{$profile->id}/verifikasi/setujui");

        $this->assertSame(VerificationStatus::Terverifikasi, $venue->fresh()->verification_status);
        $this->assertSame(VerificationStatus::Nonaktif, $disabled->fresh()->verification_status, 'Keputusan butir 3: penonaktifan individual dipertahankan.');
        $this->assertSame(MediaVisibility::Publik, $venuePhoto->fresh()->visibility);
        $this->assertSame(1, Venue::publikUntukKlien()->count());
    }

    public function test_approve_hanya_untuk_status_menunggu(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = User::factory()->vendor()->create()->vendorProfile;
        $profile->update(['verification_status' => VerificationStatus::Ditolak]);

        $this->actingAs($admin)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/setujui")
            ->assertSessionHasErrors('verification');
        $this->assertSame(VerificationStatus::Ditolak, $profile->fresh()->verification_status);
    }

    // ---- Reject ----

    public function test_admin_menolak_dengan_alasan_dan_akun_tetap_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile;
        $photo = Media::factory()->create(['owner_type' => 'vendor_profile', 'owner_id' => $profile->id]);

        $this->actingAs($admin)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/tolak", [
            'reason' => 'KTP buram, unggah ulang yang jelas.',
        ])->assertRedirect(route('admin.penyedia.index'));

        $profile->refresh();
        $this->assertSame(VerificationStatus::Ditolak, $profile->verification_status);
        $this->assertSame('KTP buram, unggah ulang yang jelas.', $profile->verification_note);
        $this->assertSame($admin->id, $profile->reviewed_by);
        $this->assertNull($profile->verified_at);
        $this->assertSame(MediaVisibility::Privat, $photo->fresh()->visibility, 'Foto tidak publik bila ditolak.');
        $this->assertSame(UserStatus::Aktif, $vendor->fresh()->status, 'T2: penolakan tidak menonaktifkan akun.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'verification_rejected',
            'reason' => 'KTP buram, unggah ulang yang jelas.',
        ]);
    }

    public function test_penolakan_wajib_menyertakan_alasan(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = User::factory()->vendor()->create()->vendorProfile;

        $this->actingAs($admin)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/tolak", [])
            ->assertSessionHasErrors('reason');
        $this->assertSame(VerificationStatus::Menunggu, $profile->fresh()->verification_status);
    }

    // ---- Ajukan ulang ----

    public function test_penyedia_ditolak_dapat_memperbaiki_dan_mengajukan_ulang(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile;
        $this->uploadDoc($vendor, 'ktp');

        $this->actingAs($admin)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/tolak", [
            'reason' => 'KTP buram.',
        ]);

        // Upload ulang menggantikan dokumen lama (butir 10), lalu ajukan lagi.
        $this->uploadDoc($vendor, 'ktp');
        $this->actingAs($vendor)->post('/verifikasi/ajukan')->assertRedirect();

        $profile->refresh();
        $this->assertSame(VerificationStatus::Menunggu, $profile->verification_status);
        $this->assertNull($profile->verification_note);
        $this->assertSame(1, $profile->media()->dokumenVerifikasi()->where('doc_type', 'ktp')->count());
    }

    // ---- Antrean & detail Admin ----

    public function test_antrean_admin_memuat_profil_menunggu(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->vendor()->create()->vendorProfile;
        $verified = User::factory()->vendor()->create()->vendorProfile;
        $verified->update(['verification_status' => VerificationStatus::Terverifikasi]);

        $this->actingAs($admin)->get('/admin/admin-penyedia')
            ->assertOk()
            ->assertViewHas('vendors', fn ($vendors) => $vendors->pluck('id')->all() === [$pending->id]);
    }

    public function test_detail_mitra_memuat_dokumen_dan_subjek(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = $vendor->vendorProfile;
        CatalogItem::factory()->create(['vendor_profile_id' => $profile->id]);
        $this->uploadDoc($vendor, 'ktp');

        $this->actingAs($admin)->get("/admin/penyedia/vendor_profile/{$profile->id}")
            ->assertOk()
            ->assertViewHas('documents', fn ($docs) => $docs->has('ktp'))
            ->assertViewHas('subjects', fn ($subjects) => $subjects->count() === 1);
    }

    public function test_tipe_profil_tidak_valid_menghasilkan_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/penyedia/user/1')->assertNotFound();
        $this->actingAs($admin)->get('/admin/penyedia/vendor_profile/999999')->assertNotFound();
    }

    // ---- Otorisasi ----

    public function test_non_admin_ditolak_memutus_verifikasi(): void
    {
        $profile = User::factory()->vendor()->create()->vendorProfile;

        foreach ([User::factory()->vendor()->create(), User::factory()->venue()->create(), User::factory()->klien()->create()] as $user) {
            $this->actingAs($user)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/setujui")->assertForbidden();
            $this->actingAs($user)->post("/admin/penyedia/vendor_profile/{$profile->id}/verifikasi/tolak", ['reason' => 'x'])->assertForbidden();
            $this->actingAs($user)->get('/admin/admin-penyedia')->assertForbidden();
        }
        $this->assertSame(VerificationStatus::Menunggu, $profile->fresh()->verification_status);
    }

    public function test_guest_dialihkan_ke_login(): void
    {
        $this->post('/verifikasi/ajukan')->assertRedirect('/login');
        $this->get('/vendor/verifikasi')->assertRedirect('/login');
    }

    // ---- Venue baru mengikuti profil terverifikasi ----

    public function test_venue_baru_penyedia_terverifikasi_langsung_terverifikasi(): void
    {
        $owner = User::factory()->venue()->create();
        $owner->venueProfile->update(['verification_status' => VerificationStatus::Terverifikasi]);

        $this->actingAs($owner)->post('/venue/data', [
            'name' => 'Venue Baru',
            'kind' => 'Aula',
            'capacity' => 200,
            'address' => 'Jl. Baru',
            'rental_price' => 10_000_000,
            'rooms' => [['name' => 'Utama', 'capacity' => 200]],
        ]);

        $venue = Venue::first();
        $this->assertSame(VerificationStatus::Terverifikasi, $venue->verification_status);
        $this->assertNotNull($venue->verified_at);
    }
}
