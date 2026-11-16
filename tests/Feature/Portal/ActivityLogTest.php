<?php

namespace Tests\Feature\Portal;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring read-only log aktivitas Admin: halaman membaca tabel `audit_logs`
 * yang ditulis oleh keputusan backend nyata, bukan baris contoh prototipe.
 * Entri palsu lama (pembayaran valid, refund, pesanan disetujui) diuji
 * tidak muncul lagi karena backend-nya (BE-4/BE-5/BE-6) belum ada.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_real_audit_entries_instead_of_prototype_rows(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Auditor Asli']);
        $profile = VendorProfile::factory()->menunggu()->create(['business_name' => 'Dapur Uji Coba']);

        AuditLog::factory()->create([
            'actor_id' => $admin->id,
            'action' => 'verification_approved',
            'subject_type' => 'vendor_profile',
            'subject_id' => $profile->id,
            'from_status' => 'menunggu',
            'to_status' => 'terverifikasi',
            'reason' => 'Dokumen dan rekening sudah diperiksa.',
        ]);

        $this->actingAs($admin)->get('/admin/aktivitas')
            ->assertOk()
            ->assertSee('Auditor Asli')
            ->assertSee('Dapur Uji Coba')
            ->assertSee('Verifikasi mitra disetujui')
            ->assertSee('menunggu → terverifikasi')
            ->assertDontSee('Pembayaran valid')
            ->assertDontSee('PK/2026/11/0026')
            ->assertDontSee('Refund dicatat');
    }

    public function test_unknown_action_is_humanised_not_hidden(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => 'ubah_status']);

        $this->actingAs($admin)->get('/admin/aktivitas')
            ->assertOk()
            ->assertSee('Ubah Status');
    }

    public function test_action_and_search_filters_are_applied_server_side(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Filter']);
        $other = User::factory()->admin()->create(['name' => 'Bukan Target Cari']);

        AuditLog::factory()->create([
            'actor_id' => $admin->id,
            'action' => 'price_change_rejected',
            'reason' => 'Rincian perubahan belum lengkap.',
        ]);
        AuditLog::factory()->create([
            'actor_id' => $other->id,
            'action' => 'verification_approved',
            'reason' => 'Setuju tanpa catatan tambahan.',
        ]);

        $this->actingAs($admin)->get('/admin/aktivitas?aksi=price_change_rejected')
            ->assertOk()
            ->assertSee('Rincian perubahan belum lengkap.')
            ->assertDontSee('Setuju tanpa catatan tambahan.');

        $this->actingAs($admin)->get('/admin/aktivitas?q=Bukan Target')
            ->assertOk()
            ->assertSee('Setuju tanpa catatan tambahan.')
            ->assertDontSee('Rincian perubahan belum lengkap.');

        // Nilai filter tak dikenal diperlakukan sebagai "semua aksi".
        $this->actingAs($admin)->get('/admin/aktivitas?aksi=tidak_ada')
            ->assertOk()
            ->assertSee('Rincian perubahan belum lengkap.')
            ->assertSee('Setuju tanpa catatan tambahan.');
    }

    public function test_detail_page_shows_stored_decision_context(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Detail']);
        $log = AuditLog::factory()->create([
            'actor_id' => $admin->id,
            'action' => 'verification_rejected',
            'from_status' => 'menunggu',
            'to_status' => 'ditolak',
            'reason' => 'KTP buram, unggah ulang.',
            'ip' => '203.0.113.9',
        ]);

        $this->actingAs($admin)->get('/admin/aktivitas/'.$log->id)
            ->assertOk()
            ->assertSee('Verifikasi mitra ditolak')
            ->assertSee('KTP buram, unggah ulang.')
            ->assertSee('203.0.113.9')
            ->assertSee('append-only')
            ->assertDontSee('Rp18.750.000')
            ->assertDontSee('LOG-2026-1103-0042');
    }

    public function test_detail_page_handles_system_actor_and_deleted_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::factory()->sistem()->create([
            'subject_type' => 'vendor_profile',
            'subject_id' => 999999,
        ]);

        $this->actingAs($admin)->get('/admin/aktivitas/'.$log->id)
            ->assertOk()
            ->assertSee('Sistem (tanpa akun)')
            ->assertSee('subjeknya sudah dihapus');
    }

    public function test_list_and_detail_render_honestly_without_data(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/aktivitas')
            ->assertOk()
            ->assertSee('Belum ada log aktivitas tercatat.');

        // Jalur catch-all prototipe lama tetap 200 dan tidak menampilkan
        // entri palsu meski tidak menerima data dari controller.
        $this->actingAs($admin)->get('/admin/aktivitas-detail')
            ->assertOk()
            ->assertSee('Belum ada entri yang dipilih.')
            ->assertDontSee('Pembayaran divalidasi');
    }

    public function test_activity_log_is_read_only_and_role_guarded(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::factory()->create(['actor_id' => $admin->id]);

        $this->get('/admin/aktivitas')->assertRedirect('/login');
        $this->get('/admin/aktivitas/'.$log->id)->assertRedirect('/login');

        $klien = User::factory()->klien()->create();
        $this->actingAs($klien)->get('/admin/aktivitas')->assertForbidden();
        $this->actingAs($klien)->get('/admin/aktivitas/'.$log->id)->assertForbidden();

        // Hanya baca: tidak ada aksi tulis pada log aktivitas.
        $this->actingAs($admin)->post('/admin/aktivitas')->assertMethodNotAllowed();

        $this->actingAs($admin)->get('/admin/aktivitas/123456')->assertNotFound();
    }
}
