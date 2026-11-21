<?php

namespace Tests\Feature\RekeningPenyedia;

use App\Enums\DisbursementStatus;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\ProviderBankAccount;
use App\Models\User;
use App\Notifications\ProviderBankAccountDecidedNotification;
use App\Notifications\ProviderBankAccountSubmittedNotification;
use App\Services\DisbursementService;
use App\Services\EventCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsPaidEvents;
use Tests\Concerns\MakesUploadedFiles;
use Tests\TestCase;

/**
 * Tahap G1: rekening pencairan penyedia — diisi Vendor/Venue, diverifikasi
 * Admin. Rekening berversi (baris terverifikasi tidak diubah di tempat),
 * nomor lengkap hanya di Profil pemilik dan halaman Admin, dan pencairan
 * tetap memakai `Disbursement::verifiedBankAccount()` yang sudah ada.
 */
class ProviderBankAccountTest extends TestCase
{
    use BuildsPaidEvents;
    use MakesUploadedFiles;
    use RefreshDatabase;

    /** @var array{bank_name: string, account_number: string, account_name: string} */
    private array $payload = [
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Dapur Nusantara',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('private');
    }

    private function vendor(): User
    {
        return User::factory()->vendor()->create();
    }

    private function venue(): User
    {
        return User::factory()->venue()->create();
    }

    /**
     * Rekening menunggu milik profil penyedia tertentu.
     */
    private function pendingFor(User $user, array $attributes = []): ProviderBankAccount
    {
        $profile = $user->providerProfile();

        return ProviderBankAccount::factory()->menunggu()->create(array_merge([
            'owner_type' => $profile->getMorphClass(),
            'owner_id' => $profile->id,
        ], $attributes));
    }

    // ---- Sisi penyedia: pengajuan ----

    public function test_penyedia_mengirim_rekening_terhubung_ke_profil_milik_user(): void
    {
        $vendor = $this->vendor();
        $other = $this->vendor();

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), $this->payload + [
            // Input kepemilikan palsu harus diabaikan sepenuhnya.
            'owner_id' => $other->vendorProfile->id,
            'owner_type' => 'vendor_profile',
            'profile_id' => $other->vendorProfile->id,
        ])->assertRedirect('/vendor/profil')->assertSessionHas('status');

        $account = ProviderBankAccount::sole();
        $this->assertSame('vendor_profile', $account->owner_type);
        $this->assertSame($vendor->vendorProfile->id, $account->owner_id);
        $this->assertSame(VerificationStatus::Menunggu, $account->verification_status);
        $this->assertSame('1234567890', $account->account_number);
        $this->assertSame(0, $other->vendorProfile->bankAccounts()->count());
    }

    public function test_validasi_rekening_menolak_input_tidak_sah(): void
    {
        $vendor = $this->vendor();

        $invalid = [
            ['account_number' => '12345'],
            ['account_number' => '123456789012345678901'],
            ['account_number' => '1234abcd90'],
            ['bank_name' => ''],
            ['account_name' => ''],
        ];

        foreach ($invalid as $override) {
            $this->actingAs($vendor)
                ->post(route('vendor.rekening.simpan'), array_merge($this->payload, $override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertSame(0, ProviderBankAccount::count());
    }

    public function test_nomor_dengan_spasi_dan_tanda_hubung_dinormalisasi(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'Bank Mandiri',
            'account_number' => '1234-5678 90',
            'account_name' => 'Dapur Nusantara',
        ])->assertRedirect('/vendor/profil');

        $this->assertSame('1234567890', ProviderBankAccount::sole()->account_number);
    }

    public function test_pengajuan_ulang_memperbarui_baris_menunggu_tanpa_menumpuk(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), $this->payload);
        $first = ProviderBankAccount::sole();

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BRI',
            'account_number' => '999888777',
            'account_name' => 'Dapur Nusantara',
        ])->assertRedirect('/vendor/profil');

        $this->assertSame(1, $vendor->vendorProfile->bankAccounts()->count());
        $updated = $first->fresh();
        $this->assertSame('BRI', $updated->bank_name);
        $this->assertSame('999888777', $updated->account_number);
        $this->assertSame(VerificationStatus::Menunggu, $updated->verification_status);
    }

    public function test_perubahan_saat_terverifikasi_membuat_baris_menunggu_dan_rekening_lama_tetap_dipakai(): void
    {
        $vendor = $this->vendor();
        $profile = $vendor->vendorProfile;
        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
        ]);

        $old = ProviderBankAccount::factory()->terverifikasi()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $profile->id,
            'bank_name' => 'BCA',
            'account_number' => '1111222233',
        ]);

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BNI',
            'account_number' => '4444555566',
            'account_name' => 'Dapur Nusantara',
        ])->assertRedirect('/vendor/profil');

        $this->assertSame(2, $profile->bankAccounts()->count());
        $this->assertSame(VerificationStatus::Terverifikasi, $old->fresh()->verification_status);
        $this->assertSame($old->id, $disbursement->fresh()->verifiedBankAccount()?->id);
    }

    public function test_hanya_route_rekening_sesuai_peran_yang_melayani(): void
    {
        $vendor = $this->vendor();
        $venue = $this->venue();

        $this->actingAs($venue)->post(route('vendor.rekening.simpan'), $this->payload)->assertStatus(403);
        $this->actingAs($vendor)->post(route('venue.rekening.simpan'), $this->payload)->assertStatus(403);
        $this->assertSame(0, ProviderBankAccount::count());
    }

    public function test_vendor_tidak_melihat_atau_mengubah_rekening_vendor_lain(): void
    {
        $vendorA = $this->vendor();
        $vendorB = $this->vendor();
        $accountB = $this->pendingFor($vendorB, ['account_number' => '5555666677']);

        $this->actingAs($vendorA)->get('/vendor/profil')
            ->assertOk()
            ->assertDontSee('5555666677');

        $this->actingAs($vendorA)->post(route('vendor.rekening.simpan'), $this->payload)
            ->assertRedirect('/vendor/profil');

        $this->assertSame('5555666677', $accountB->fresh()->account_number);
        $this->assertSame(1, $vendorB->vendorProfile->bankAccounts()->count());
    }

    // ---- Sisi Admin: keputusan ----

    public function test_admin_menyetujui_rekening_menonaktifkan_rekening_lama(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $profile = $vendor->vendorProfile;

        $old = ProviderBankAccount::factory()->terverifikasi()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $profile->id,
            'account_number' => '1111222233',
        ]);
        $pending = $this->pendingFor($vendor, ['account_number' => '9999000011']);
        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $profile->id,
        ]);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()])
            ->assertRedirect(route('admin.penyedia.detail', ['type' => 'vendor_profile', 'id' => $profile->id]))
            ->assertSessionHas('status');

        $this->assertSame(VerificationStatus::Terverifikasi, $pending->fresh()->verification_status);
        $this->assertSame(VerificationStatus::Nonaktif, $old->fresh()->verification_status);
        $this->assertSame($admin->id, $pending->fresh()->reviewed_by);
        $this->assertNotNull($pending->fresh()->reviewed_at);
        $this->assertSame($pending->id, $disbursement->fresh()->verifiedBankAccount()?->id);

        $log = AuditLog::where('action', 'provider_bank_account_approved')->sole();
        $this->assertSame('provider_bank_account', $log->subject_type);
        $this->assertStringNotContainsString('9999000011', (string) $log->reason);
        $this->assertStringContainsString('•••• 0011', (string) $log->reason);

        Notification::assertSentTo($vendor, ProviderBankAccountDecidedNotification::class, function (ProviderBankAccountDecidedNotification $notification) use ($vendor): bool {
            return $notification->decision === 'disetujui'
                && ! str_contains(json_encode($notification->toArray($vendor)), '9999000011');
        });
    }

    public function test_admin_menolak_wajib_alasan_dan_alasan_tampil_di_profil(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending), ['token' => $pending->reviewToken()])
            ->assertSessionHasErrors('reason');
        $this->assertSame(VerificationStatus::Menunggu, $pending->fresh()->verification_status);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending), [
            'token' => $pending->reviewToken(),
            'reason' => 'Nama rekening tidak sesuai nama usaha.',
        ])->assertSessionHas('status');

        $decided = $pending->fresh();
        $this->assertSame(VerificationStatus::Ditolak, $decided->verification_status);
        $this->assertSame('Nama rekening tidak sesuai nama usaha.', $decided->rejection_reason);

        $this->actingAs($vendor)->get('/vendor/profil')
            ->assertOk()
            ->assertSee('Nama rekening tidak sesuai nama usaha.');

        Notification::assertSentTo($vendor, ProviderBankAccountDecidedNotification::class, function (ProviderBankAccountDecidedNotification $notification): bool {
            return $notification->decision === 'ditolak' && $notification->reason === 'Nama rekening tidak sesuai nama usaha.';
        });
    }

    public function test_penolakan_membuat_pencairan_tetap_terblokir(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);
        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
        ]);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending), [
            'token' => $pending->reviewToken(),
            'reason' => 'Nomor rekening tidak valid.',
        ]);

        $this->actingAs($admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => now()->toDateString(),
            'proof' => $this->pdfFile('bukti.pdf'),
        ])->assertSessionHas('error', DisbursementService::MESSAGE_NO_BANK_ACCOUNT);

        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->fresh()->status);
    }

    public function test_keputusan_ganda_ditolak_tanpa_efek_ganda(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()])->assertSessionHas('status');
        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->fresh()->reviewToken()])
            ->assertSessionHasErrors('bank_account');
        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending->fresh()), ['token' => $pending->fresh()->reviewToken(), 'reason' => 'Terlambat.'])
            ->assertSessionHasErrors('bank_account');

        $this->assertSame(VerificationStatus::Terverifikasi, $pending->fresh()->verification_status);
        $this->assertSame(1, AuditLog::whereIn('action', ['provider_bank_account_approved', 'provider_bank_account_rejected'])->count());
    }

    public function test_akses_keputusan_admin_hanya_untuk_admin(): void
    {
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);
        $otherVendor = $this->vendor();
        $klien = User::factory()->klien()->create();

        $this->actingAs($klien)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()])->assertStatus(403);
        $this->actingAs($otherVendor)->post(route('admin.rekening-penyedia.reject', $pending), ['reason' => 'x'])->assertStatus(403);

        $this->assertSame(VerificationStatus::Menunggu, $pending->fresh()->verification_status);
    }

    // ---- Notifikasi & penyamaran ----

    public function test_admin_menerima_notifikasi_pengajuan_tanpa_nomor_lengkap(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), $this->payload);

        Notification::assertSentTo($admin, ProviderBankAccountSubmittedNotification::class, function (ProviderBankAccountSubmittedNotification $notification) use ($admin): bool {
            $payload = $notification->toArray($admin);

            return ! str_contains($payload['message'], '1234567890')
                && str_contains($payload['message'], '•••• 7890')
                && $payload['url'] === route('admin.penyedia.detail', ['type' => 'vendor_profile', 'id' => $notification->bankAccount->owner_id]);
        });
    }

    public function test_nomor_lengkap_hanya_di_profil_pemilik_dan_halaman_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
        ]);

        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BCA',
            'account_number' => '1357246810',
            'account_name' => 'Dapur Nusantara',
        ])->assertRedirect('/vendor/profil');

        // Pemilik melihat nomor penuh hanya di halaman Profil.
        $this->actingAs($vendor)->get('/vendor/profil')->assertOk()->assertSee('1357246810');

        // Dashboard dan halaman Pencairan penyedia tidak memuat nomor penuh.
        $this->actingAs($vendor)->get('/vendor')->assertOk()->assertDontSee('1357246810');
        $this->actingAs($vendor)->get('/vendor/pencairan')->assertOk()->assertDontSee('1357246810');

        // Admin melihat nomor penuh di detail penyedia dan halaman Pencairan.
        $this->actingAs($admin)->get(route('admin.penyedia.detail', ['type' => 'vendor_profile', 'id' => $vendor->vendorProfile->id]))
            ->assertOk()->assertSee('1357246810');
        $this->actingAs($admin)->get(route('admin.pencairan'))->assertOk()->assertDontSee('1357246810');

        // Log audit tidak pernah memuat nomor lengkap.
        $log = AuditLog::where('action', 'provider_bank_account_submitted')->sole();
        $this->assertStringNotContainsString('1357246810', (string) $log->reason);
        $this->assertStringContainsString('•••• 6810', (string) $log->reason);
    }

    // ---- Banner kondisi rekening ----

    public function test_banner_rekening_belum_diverifikasi_muncul_lalu_hilang(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();

        foreach (['/vendor', '/vendor/pencairan'] as $url) {
            $this->actingAs($vendor)->get($url)->assertOk()->assertSee('Rekening pencairan belum diverifikasi');
        }

        $pending = $this->pendingFor($vendor);
        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()]);

        foreach (['/vendor', '/vendor/pencairan'] as $url) {
            $this->actingAs($vendor)->get($url)->assertOk()->assertDontSee('Rekening pencairan belum diverifikasi');
        }
    }

    public function test_venue_memakai_alur_rekening_yang_sama(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = $this->venue();

        $this->actingAs($venue)->get('/venue/profil')->assertOk()->assertSee('Rekening pencairan');
        $this->actingAs($venue)->post(route('venue.rekening.simpan'), [
            'bank_name' => 'BNI',
            'account_number' => '0987654321',
            'account_name' => 'Graha Melati',
        ])->assertRedirect('/venue/profil');

        $account = ProviderBankAccount::sole();
        $this->assertSame('venue_profile', $account->owner_type);
        $this->assertSame($venue->venueProfile->id, $account->owner_id);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $account), ['token' => $account->reviewToken()])->assertSessionHas('status');
        $this->assertSame(VerificationStatus::Terverifikasi, $account->fresh()->verification_status);
    }

    // ---- Daftar, dashboard, dan halaman Pencairan Admin ----

    public function test_dashboard_admin_menghitung_rekening_menunggu(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $this->pendingFor($vendor);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Rekening penyedia menunggu verifikasi')
            ->assertSee(route('admin.penyedia.index', ['rekening' => 'menunggu']), false);
    }

    public function test_daftar_penyedia_menampilkan_lencana_dan_filter_rekening(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingVendor = $this->vendor();
        $this->pendingFor($pendingVendor);
        $terverifikasi = $this->vendor();
        ProviderBankAccount::factory()->terverifikasi()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $terverifikasi->vendorProfile->id,
        ]);

        $this->actingAs($admin)->get(route('admin.penyedia.index', ['rekening' => 'menunggu']))
            ->assertOk()
            ->assertSee($pendingVendor->vendorProfile->business_name)
            ->assertDontSee($terverifikasi->vendorProfile->business_name);

        $this->actingAs($admin)->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('Terverifikasi');
    }

    public function test_halaman_pencairan_admin_menampilkan_tautan_verifikasi_dan_status_tombol(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor, ['account_name' => 'Dapur Nusantara', 'account_number' => '1234567890']);
        $disbursement = Disbursement::factory()->create([
            'provider_type' => 'vendor_profile',
            'provider_id' => $vendor->vendorProfile->id,
        ]);

        $this->actingAs($admin)->get(route('admin.pencairan'))
            ->assertOk()
            ->assertSee('Rekening menunggu verifikasi')
            ->assertSee('Verifikasi rekening')
            ->assertSee(route('admin.penyedia.detail', ['type' => 'vendor_profile', 'id' => $vendor->vendorProfile->id]), false);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()]);

        $this->actingAs($admin)->get(route('admin.pencairan'))
            ->assertOk()
            ->assertSee('a.n. Dapur Nusantara')
            ->assertSee('1234567890')
            ->assertDontSee('disabled title=')
            ->assertSee('Catat transfer');

        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->fresh()->status);
    }

    // ---- H1: token persetujuan atas data yang dilihat Admin ----

    public function test_token_basi_memblokir_persetujuan_tanpa_mengubah_data(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor, ['account_number' => '1111111111']);
        $oldToken = $pending->reviewToken();

        // Penyedia mengubah pengajuan menunggu (nomor berbeda) di detik berikutnya.
        $this->travel(2)->seconds();
        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BNI',
            'account_number' => '2222222222',
            'account_name' => 'Dapur Nusantara',
        ])->assertRedirect('/vendor/profil');

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $oldToken])
            ->assertRedirect(route('admin.penyedia.detail', ['type' => 'vendor_profile', 'id' => $vendor->vendorProfile->id]))
            ->assertSessionHasErrors('bank_account');

        $this->assertSame(VerificationStatus::Menunggu, $pending->fresh()->verification_status);
        $this->assertSame('2222222222', $pending->fresh()->account_number);
        $this->assertSame(0, ProviderBankAccount::where('verification_status', VerificationStatus::Terverifikasi)->count());
        $this->assertSame(0, AuditLog::where('action', 'provider_bank_account_approved')->count());
    }

    public function test_token_basi_memblokir_penolakan_tanpa_mengubah_data(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor, ['account_number' => '3333333333']);
        $oldToken = $pending->reviewToken();

        $this->travel(2)->seconds();
        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BRI',
            'account_number' => '4444444444',
            'account_name' => 'Dapur Nusantara',
        ]);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending), [
            'token' => $oldToken,
            'reason' => 'Nomor tidak sesuai.',
        ])->assertSessionHasErrors('bank_account');

        $this->assertSame(VerificationStatus::Menunggu, $pending->fresh()->verification_status);
        $this->assertNull($pending->fresh()->rejection_reason);
        $this->assertSame(0, AuditLog::where('action', 'provider_bank_account_rejected')->count());
    }

    public function test_tanpa_token_keputusan_ditolak(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), [])
            ->assertSessionHasErrors('bank_account');

        $this->assertSame(VerificationStatus::Menunggu, $pending->fresh()->verification_status);
        $this->assertSame(0, AuditLog::where('action', 'provider_bank_account_approved')->count());
    }

    public function test_token_benar_memungkinkan_keputusan(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), [
            'token' => $pending->reviewToken(),
        ])->assertSessionHas('status');

        $this->assertSame(VerificationStatus::Terverifikasi, $pending->fresh()->verification_status);
    }

    // ---- H2/H3: pesan penolakan basi dan prefill kartu ----

    public function test_penolakan_lama_tidak_tampil_setelah_pengajuan_baru_disetujui(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = $this->vendor();
        $pending = $this->pendingFor($vendor, ['account_number' => '5555555555']);

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.reject', $pending), [
            'token' => $pending->reviewToken(),
            'reason' => 'Alasan lama yang tidak boleh muncul lagi.',
        ]);

        $this->travel(2)->seconds();
        $this->actingAs($vendor)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BCA',
            'account_number' => '6666666666',
            'account_name' => 'Dapur Nusantara',
        ]);

        $newPending = $vendor->vendorProfile->bankAccounts()
            ->where('verification_status', VerificationStatus::Menunggu->value)->sole();

        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $newPending), [
            'token' => $newPending->reviewToken(),
        ])->assertSessionHas('status');

        $this->actingAs($vendor)->get('/vendor/profil')
            ->assertOk()
            ->assertSee('Terverifikasi')
            ->assertDontSee('Alasan lama yang tidak boleh muncul lagi.');
    }

    public function test_kartu_rekening_memakai_data_rekening_aktif_saat_tidak_ada_pengajuan(): void
    {
        $vendor = $this->vendor();
        ProviderBankAccount::factory()->terverifikasi()->create([
            'owner_type' => 'vendor_profile',
            'owner_id' => $vendor->vendorProfile->id,
            'bank_name' => 'BCA',
            'account_number' => '7777777777',
            'account_name' => 'Dapur Nusantara',
        ]);

        $this->actingAs($vendor)->get('/vendor/profil')
            ->assertOk()
            ->assertSee('value="BCA"', false)
            ->assertSee('value="7777777777"', false)
            ->assertSee('value="Dapur Nusantara"', false);
    }

    // ---- Integrasi alur uang ----

    public function test_alur_uang_rekening_disetujui_lewat_route_lalu_transfer_dicatat(): void
    {
        $this->travelTo(Carbon::parse('2026-12-16 09:00:00'));

        $admin = User::factory()->admin()->create();
        $paid = $this->paidReferenceEvent('2026-12-15');
        app(EventCompletionService::class)->complete($paid['event'], $admin);

        $item = $paid['cateringItem']->fresh();
        $providerUser = $item->provider->user;
        $disbursement = Disbursement::where('order_item_id', $item->id)->sole();
        $this->assertSame(DisbursementStatus::BelumCair, $disbursement->status);
        $this->assertNull($disbursement->verifiedBankAccount());

        // Penyedia mengisi rekening lewat route; status tidak diisi lewat factory.
        $this->actingAs($providerUser)->post(route('vendor.rekening.simpan'), [
            'bank_name' => 'BCA',
            'account_number' => '1234509876',
            'account_name' => 'Katering Demo',
        ])->assertRedirect('/vendor/profil');

        $pending = $providerUser->vendorProfile->bankAccounts()->sole();
        $this->assertSame(VerificationStatus::Menunggu, $pending->verification_status);

        // Admin menyetujui lewat route.
        $this->actingAs($admin)->post(route('admin.rekening-penyedia.approve', $pending), ['token' => $pending->reviewToken()])->assertSessionHas('status');
        $this->assertSame($pending->id, $disbursement->fresh()->verifiedBankAccount()?->id);

        // Transfer dicatat Admin ke rekening terverifikasi tersebut.
        $this->actingAs($admin)->post(route('admin.pencairan.record', $disbursement), [
            'paid_at' => '2026-12-16',
            'proof' => $this->pdfFile('transfer.pdf'),
        ])->assertRedirect(route('admin.pencairan', ['tab' => 'cair']));

        $paidDisbursement = $disbursement->fresh();
        $this->assertSame(DisbursementStatus::Cair, $paidDisbursement->status);
        $this->assertSame($admin->id, $paidDisbursement->recorded_by);
        $this->assertNotNull($paidDisbursement->proof);
    }
}
