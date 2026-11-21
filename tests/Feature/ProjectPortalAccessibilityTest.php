<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE-1 (B18): aksesibilitas drawer portal diuji sebagai pengguna login
 * dengan role sesuai, bukan sebagai guest.
 */
class ProjectPortalAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_portal_drawer_exposes_named_keyboard_controls(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            $response = $this->actingAs($user)->get("/{$role}");

            $response->assertOk()
                ->assertSee(
                    '<button type="button" class="burger" id="nav-toggle" aria-label="Buka menu navigasi" aria-controls="main-navigation" aria-expanded="false">',
                    false
                )
                ->assertSee('id="main-navigation"', false)
                ->assertSee(
                    '<button type="button" class="sidebar-close" aria-label="Tutup menu navigasi">',
                    false
                );
        }
    }
}
