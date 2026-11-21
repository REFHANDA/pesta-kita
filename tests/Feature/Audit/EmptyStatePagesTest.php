<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap G5: tidak ada halaman yang gagal (500) pada data kosong.
 * Penyedia diuji tanpa rekening, tanpa foto, tanpa pesanan, dan tanpa
 * katalog/venue; Klien tanpa acara; Admin pada basis data yang hanya
 * berisi akunnya sendiri.
 */
class EmptyStatePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman yang harus tetap 200 pada kondisi kosong.
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function portalPages(): array
    {
        return [
            'vendor' => ['vendor', [
                '/vendor', '/vendor/profil', '/vendor/verifikasi', '/vendor/layanan', '/vendor/peralatan',
                '/vendor/pesanan', '/vendor/pencairan', '/vendor/rundown', '/vendor/notifikasi',
                '/vendor/pengajuan-harga', '/vendor/item-form', '/vendor/peralatan-form',
            ]],
            'venue' => ['venue', [
                '/venue', '/venue/profil', '/venue/verifikasi', '/venue/data', '/venue/form',
                '/venue/pesanan', '/venue/pencairan', '/venue/rundown', '/venue/notifikasi',
                '/venue/pengajuan-harga',
            ]],
            'klien' => ['klien', [
                '/klien', '/klien/acara', '/klien/notifikasi', '/klien/profil', '/klien/acara-baru',
            ]],
            'admin' => ['admin', [
                '/admin', '/admin/acara', '/admin/pesanan', '/admin/pembayaran', '/admin/pembatalan',
                '/admin/pencairan', '/admin/pengaturan', '/admin/rekening', '/admin/rekening-form',
                '/admin/aktivitas', '/admin/admin-penyedia', '/admin/pengguna', '/admin/katalog',
                '/admin/pengajuan-harga', '/admin/notifikasi',
            ]],
        ];
    }

    public function test_halaman_portal_tetap_200_pada_data_kosong(): void
    {
        foreach (self::portalPages() as $role => [$factoryState, $urls]) {
            $user = User::factory()->{$factoryState}()->create();

            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }
}
