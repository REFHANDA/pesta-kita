<?php

namespace Tests\Feature\Simplification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu sidebar tiap portal persis sesuai rancangan Tahap A dan tidak memuat
 * tautan mati (setiap tautan menu menghasilkan 200 untuk role pemiliknya).
 */
class PortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, list<string>>
     */
    protected function expectedMenus(): array
    {
        return [
            'klien' => ['Beranda', 'Acara saya', 'Katalog', 'Notifikasi', 'Profil'],
            'vendor' => ['Beranda', 'Katalog saya', 'Pesanan', 'Rundown', 'Pencairan', 'Profil & verifikasi'],
            'venue' => ['Beranda', 'Venue saya', 'Pesanan', 'Rundown', 'Pencairan', 'Profil & verifikasi'],
            'admin' => ['Beranda', 'Pesanan', 'Tagihan & pembayaran', 'Pembatalan & refund', 'Pencairan', 'Penyedia & katalog', 'Acara', 'Pengaturan', 'Log aktivitas'],
        ];
    }

    /**
     * @return list<array{label: string, href: string, active: bool}>
     */
    protected function menuLinks(string $html): array
    {
        preg_match('/<nav[^>]*data-portal-nav[^>]*>(.*?)<\/nav>/s', $html, $nav);
        $this->assertNotEmpty($nav, 'Menu portal tidak ditemukan.');

        preg_match_all('/<a href="([^"]+)"([^>]*)>(.*?)<\/a>/s', $nav[1], $matches, PREG_SET_ORDER);

        return array_map(fn (array $m): array => [
            'label' => html_entity_decode(trim(strip_tags($m[3]))),
            'href' => $m[1],
            'active' => str_contains($m[2], 'aria-current="page"'),
        ], $matches);
    }

    public function test_menu_tiap_portal_persis_dan_tanpa_tautan_mati(): void
    {
        foreach ($this->expectedMenus() as $role => $labels) {
            $user = User::factory()->{$role}()->create();
            $links = $this->menuLinks($this->actingAs($user)->get("/{$role}")->assertOk()->getContent());

            $this->assertSame($labels, array_column($links, 'label'), "Menu {$role} tidak sesuai.");

            foreach ($links as $link) {
                $this->actingAs($user)->get($link['href'])->assertOk();
            }
        }
    }

    public function test_menu_induk_tetap_aktif_di_halaman_tab_anak(): void
    {
        $cases = [
            'klien' => ['/klien/acara?tab=riwayat' => 'Acara saya', '/klien/katalog-peralatan' => 'Katalog'],
            'vendor' => ['/vendor/peralatan' => 'Katalog saya', '/vendor/pengajuan-harga' => 'Katalog saya', '/vendor/pesanan?status=keberatan' => 'Pesanan', '/vendor/verifikasi' => 'Profil & verifikasi'],
            'venue' => ['/venue/pengajuan-harga' => 'Venue saya', '/venue/verifikasi' => 'Profil & verifikasi'],
            'admin' => ['/admin/katalog' => 'Penyedia & katalog', '/admin/pengguna' => 'Penyedia & katalog', '/admin/pengajuan-harga' => 'Penyedia & katalog', '/admin/rekening' => 'Pengaturan', '/admin/pesanan?tab=riwayat' => 'Pesanan'],
        ];

        foreach ($cases as $role => $pages) {
            $user = User::factory()->{$role}()->create();

            foreach ($pages as $path => $activeLabel) {
                $links = $this->menuLinks($this->actingAs($user)->get($path)->assertOk()->getContent());
                $active = array_values(array_filter($links, fn (array $l): bool => $l['active']));

                $this->assertCount(1, $active, "Harus tepat satu menu aktif di {$path}.");
                $this->assertSame($activeLabel, $active[0]['label'], "Menu aktif salah di {$path}.");
            }
        }
    }

    public function test_header_tanpa_pencarian_global_dan_tanpa_label_seksi(): void
    {
        foreach (array_keys($this->expectedMenus()) as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get("/{$role}")
                ->assertOk()
                ->assertDontSee('role="search"', false)
                ->assertDontSee('type="search"', false)
                ->assertDontSee('class="nav-label"', false)
                ->assertDontSee('kalender-ketersediaan');
        }
    }
}
