<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-1 (B18): halaman portal hanya dapat diakses oleh akun login dengan role sesuai.
 * Test memakai pengguna factory per portal, bukan akses guest.
 */
class ProjectPortalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_updated_role_pages_are_reachable(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            foreach (glob(resource_path("views/{$role}/*.blade.php")) as $view) {
                $page = basename($view, '.blade.php');

                // Halaman login portal adalah rute guest eksplisit, bukan
                // halaman portal; diuji terpisah di Auth\LoginTest.
                if ($page === 'login') {
                    continue;
                }

                $path = $page === 'index' ? "/{$role}" : "/{$role}/{$page}";

                $this->assertDoesNotMatchRegularExpression(
                    '/(?:href|data-after|data-go)\s*=\s*["\'][^"\']+\.html|location\.href\s*=\s*["\'][^"\']+\.html/i',
                    file_get_contents($view),
                    "Unconverted prototype link in {$view}"
                );
                $this->actingAs($user)->get($path)->assertOk();
            }
        }
    }
}
