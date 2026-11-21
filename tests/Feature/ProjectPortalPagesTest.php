<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Halaman portal hanya dapat diakses lewat route eksplisit bernama oleh akun
 * login dengan role sesuai. Tidak ada route catch-all /{page}.
 */
class ProjectPortalPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, list<string>>
     */
    protected function portalPages(): array
    {
        return [
            'klien' => ['', '/acara', '/acara?tab=riwayat', '/acara-baru', '/katalog', '/katalog-vendor', '/katalog-peralatan', '/notifikasi', '/profil'],
            'vendor' => ['', '/layanan', '/peralatan', '/pengajuan-harga', '/pesanan', '/rundown', '/pencairan', '/profil', '/verifikasi', '/notifikasi'],
            'venue' => ['', '/data', '/pengajuan-harga', '/pesanan', '/rundown', '/pencairan', '/profil', '/verifikasi', '/notifikasi'],
            'admin' => ['', '/pesanan', '/pembayaran', '/pembatalan', '/pencairan', '/admin-penyedia', '/katalog', '/pengajuan-harga', '/pengguna', '/acara', '/pengaturan', '/aktivitas', '/notifikasi', '/profil'],
        ];
    }

    public function test_halaman_portal_eksplisit_dapat_diakses_role_pemilik(): void
    {
        foreach ($this->portalPages() as $role => $pages) {
            $user = User::factory()->{$role}()->create();

            foreach ($pages as $page) {
                $this->actingAs($user)->get("/{$role}{$page}")->assertOk();
            }
        }
    }

    public function test_tidak_ada_route_catch_all_dan_url_acak_404(): void
    {
        foreach (['klien', 'vendor', 'venue', 'admin'] as $role) {
            $this->assertFalse(Route::has("{$role}.page"), "Route catch-all {$role}.page masih ada.");

            $user = User::factory()->{$role}()->create();
            $this->actingAs($user)->get("/{$role}/halaman-acak-tidak-ada")->assertNotFound();
        }

        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('{page}', $route->uri(), "Masih ada route catch-all: {$route->uri()}");
        }
    }

    public function test_halaman_yang_dihapus_mengembalikan_404(): void
    {
        $deleted = [
            'klien' => ['/kalender-ketersediaan', '/pesanan', '/pesanan-detail', '/katalog-detail', '/acara-batalkan', '/evaluasi-selesai', '/tagihan-ditolak', '/register'],
            'vendor' => ['/kalender-ketersediaan', '/keberatan', '/pencairan-detail', '/registrasi-penyedia'],
            'venue' => ['/kalender-ketersediaan', '/keberatan', '/pencairan-detail'],
            'admin' => ['/admin-pesanan-venue', '/katalog-form', '/keberatan', '/keberatan-detail', '/notifikasi-detail', '/pembatalan-detail', '/pencairan-detail', '/pengguna-detail', '/pengguna-form', '/tagihan-detail', '/rundown', '/evaluasi', '/evaluasi-detail'],
        ];

        foreach ($deleted as $role => $pages) {
            $user = User::factory()->{$role}()->create();

            foreach ($pages as $page) {
                $this->actingAs($user)->get("/{$role}{$page}")->assertNotFound();
            }
        }
    }

    public function test_riwayat_klien_lama_dialihkan_ke_tab_riwayat(): void
    {
        $this->actingAs(User::factory()->klien()->create())
            ->get('/klien/riwayat')
            ->assertRedirect('/klien/acara?tab=riwayat');
    }

    public function test_akses_lintas_role_ditolak(): void
    {
        $roles = ['klien', 'vendor', 'venue', 'admin'];

        foreach ($roles as $role) {
            $user = User::factory()->{$role}()->create();

            foreach (array_diff($roles, [$role]) as $other) {
                $this->actingAs($user)->get("/{$other}")->assertForbidden();
                $this->actingAs($user)->get("/{$other}/profil")->assertForbidden();
            }
        }
    }
}
