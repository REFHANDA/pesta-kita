<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-1 §8: proteksi portal — guest ditolak, matriks role × portal lengkap
 * (beranda dan catch-all), akun nonaktif di tengah session, dan proteksi
 * endpoint POST.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman contoh per portal: beranda + halaman catch-all.
     *
     * @var array<string, array<int, string>>
     */
    private const PORTAL_PAGES = [
        'admin' => ['/admin', '/admin/profil', '/admin/pengguna'],
        'klien' => ['/klien', '/klien/profil', '/klien/acara'],
        'vendor' => ['/vendor', '/vendor/profil', '/vendor/layanan'],
        'venue' => ['/venue', '/venue/profil', '/venue/data'],
    ];

    public function test_guests_are_redirected_to_login_from_every_portal_page(): void
    {
        foreach (self::PORTAL_PAGES as $pages) {
            foreach ($pages as $path) {
                $this->get($path)->assertRedirect('/login');
            }
        }
    }

    public function test_role_portal_access_matrix(): void
    {
        $roles = ['admin', 'klien', 'vendor', 'venue'];

        foreach ($roles as $actor) {
            $user = User::factory()->{$actor}()->create();

            foreach ($roles as $portal) {
                foreach (self::PORTAL_PAGES[$portal] as $path) {
                    $response = $this->actingAs($user)->get($path);

                    if ($actor === $portal) {
                        $response->assertOk();
                    } else {
                        $response->assertForbidden();
                    }
                }
            }
        }
    }

    public function test_inactive_user_is_logged_out_mid_session(): void
    {
        $user = User::factory()->klien()->create();
        $this->actingAs($user)->get('/klien')->assertOk();

        // Admin menonaktifkan akun saat session masih aktif.
        $user->update(['status' => UserStatus::Nonaktif]);

        $response = $this->get('/klien');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Session lama tidak bisa dipakai lagi.
        $this->get('/klien/profil')->assertRedirect('/login');
    }

    public function test_nonaktif_factory_user_cannot_access_portal(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->nonaktif()->create();

            $this->actingAs($user)->get("/{$role}")->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_guests_cannot_post_to_write_endpoints(): void
    {
        $this->post('/profil', ['name' => 'X'])->assertRedirect('/login');
        $this->post('/profil/sandi', [])->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unknown_portal_page_returns_404_for_correct_role(): void
    {
        $user = User::factory()->klien()->create();

        $this->actingAs($user)->get('/klien/halaman-tidak-ada')->assertNotFound();
    }

    public function test_landing_and_auth_pages_remain_public(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/klien/login')->assertOk();
        $this->get('/vendor/login')->assertOk();
        $this->get('/admin/login')->assertOk();
    }
}
