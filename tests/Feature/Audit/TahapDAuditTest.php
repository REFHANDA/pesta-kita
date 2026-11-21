<?php

namespace Tests\Feature\Audit;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Audit Tahap D3a: keamanan route, tautan rusak, teks prototipe, URL lama,
 * dan larangan aritmetika uang berbasis float.
 */
class TahapDAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Route portal yang wajib memakai middleware peran.
     *
     * @return array<int, string>
     */
    protected function portalRouteNames(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (preg_match('/^(klien|vendor|venue|admin)\./', $name) === 1) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function test_semua_route_portal_memakai_middleware_peran(): void
    {
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (preg_match('/^(klien|vendor|venue|admin)\./', $name) !== 1 || str_ends_with($name, '.login')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasRole = collect($middleware)->contains(fn ($item) => is_string($item) && str_starts_with($item, 'role:'));
            $hasAuth = collect($middleware)->contains(fn ($item) => $item === 'auth' || (is_string($item) && str_starts_with($item, 'auth:')));

            if (! $hasRole || ! $hasAuth) {
                $offenders[] = $name.' ['.implode(',', array_filter($middleware, 'is_string')).']';
            }
        }

        $this->assertSame([], $offenders, 'Route portal tanpa middleware peran: '.implode('; ', $offenders));
        $this->assertNotEmpty($this->portalRouteNames());
    }

    public function test_halaman_utama_tiap_portal_tidak_500_dan_tanpa_tautan_placeholder(): void
    {
        $client = User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Draft,
        ]);

        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();
        $admin = User::factory()->admin()->create();

        $pages = [
            [$client, ['/klien', '/klien/acara', '/klien/katalog', '/klien/notifikasi', '/klien/profil', route('klien.acara.detail', $event)]],
            [$vendor, ['/vendor', route('vendor.layanan'), '/vendor/pesanan', '/vendor/rundown', '/vendor/pencairan', '/vendor/profil']],
            [$venue, ['/venue', '/venue/data', '/venue/pesanan', '/venue/rundown', '/venue/pencairan', '/venue/profil']],
            [$admin, ['/admin', '/admin/acara', '/admin/pesanan', '/admin/pembayaran', '/admin/pembatalan', '/admin/pencairan', '/admin/pengaturan', '/admin/aktivitas']],
        ];

        foreach ($pages as [$user, $urls]) {
            foreach ($urls as $url) {
                $response = $this->actingAs($user)->get($url);
                $this->assertSame(200, $response->status(), "Halaman {$url} mengembalikan {$response->status()}.");

                $html = $response->getContent();
                $this->assertStringNotContainsString('href="#"', $html, "Tautan placeholder di {$url}.");
                $this->assertStringNotContainsString('javascript:void', $html, "Tautan javascript:void di {$url}.");
            }
        }
    }

    public function test_tidak_ada_teks_prototipe_di_kode_dan_view(): void
    {
        $banned = [
            'belum terhubung',
            'prototipe',
            'lorem ipsum',
            'data dummy',
            'dummy data',
            'TODO',
            'FIXME',
            'coming soon',
            'akan hadir',
            'segera hadir',
        ];

        $finder = (new Finder)
            ->files()
            ->in([base_path('app'), base_path('resources/views'), base_path('routes')])
            ->name(['*.php', '*.blade.php']);

        $offenders = [];
        foreach ($finder as $file) {
            $contents = strtolower($file->getContents());
            foreach ($banned as $needle) {
                if (str_contains($contents, strtolower($needle))) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getRealPath()).' -> '.$needle;
                }
            }
        }

        $this->assertSame([], $offenders, 'Teks prototipe ditemukan: '.implode('; ', $offenders));
    }

    public function test_url_lama_tahap_c_dan_d_404(): void
    {
        $client = User::factory()->klien()->create(['role' => 'klien']);
        $vendor = User::factory()->vendor()->create();
        $venue = User::factory()->venue()->create();

        $this->actingAs($client)->get('/klien/rundown')->assertNotFound();
        $this->actingAs($client)->get('/klien/evaluasi')->assertNotFound();
        $this->actingAs($client)->get('/klien/rekomendasi')->assertNotFound();
        $this->actingAs($vendor)->get('/vendor/layout')->assertNotFound();
        $this->actingAs($venue)->get('/venue/denah')->assertNotFound();
    }

    public function test_tidak_ada_aritmetika_uang_berbasis_float(): void
    {
        $finder = (new Finder)
            ->files()
            ->in([base_path('app/Services'), base_path('app/Http')])
            ->name('*.php');

        // Rata-rata rating memang desimal; bukan uang.
        $allowed = [
            'app/Services/RatingSummary.php',
            'app/Services/PackageRecommender.php',
        ];

        $offenders = [];
        foreach ($finder as $file) {
            $relative = str_replace(base_path().'/', '', $file->getRealPath());
            if (in_array($relative, $allowed, true)) {
                continue;
            }

            $contents = $file->getContents();
            foreach (['round(', 'floor(', 'ceil(', '(float)'] as $needle) {
                if (str_contains($contents, $needle)) {
                    $offenders[] = $relative.' -> '.$needle;
                }
            }
        }

        $this->assertSame([], $offenders, 'Aritmetika float pada jalur uang: '.implode('; ', $offenders));
    }
}
