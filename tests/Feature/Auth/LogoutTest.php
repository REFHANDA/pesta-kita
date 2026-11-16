<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-1 §8: logout mengakhiri sesi, meng-invalidate session, dan
 * mengembalikan pengguna ke /login. Hanya menerima POST.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->klien()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_so_portal_is_no_longer_accessible(): void
    {
        $user = User::factory()->klien()->create();

        $this->actingAs($user)->get('/klien')->assertOk();
        $this->post('/logout');

        $this->get('/klien')->assertRedirect('/login');
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_only_accepts_post(): void
    {
        $user = User::factory()->klien()->create();

        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_pages_contain_logout_form(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get("/{$role}/profil")
                ->assertOk()
                ->assertSee('action="'.url('/logout').'"', false);
        }
    }
}
