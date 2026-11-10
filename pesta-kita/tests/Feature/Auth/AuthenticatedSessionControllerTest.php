<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_posts_to_the_web_login_route(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_valid_client_credentials_start_a_session_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'client@example.test',
            'password' => 'password123',
            'role' => 'client',
        ]);

        $this->post('/login', [
            'email' => 'client@example.test',
            'password' => 'password123',
        ])->assertRedirect(route('klien.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_client_visiting_login_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('klien.dashboard'));
    }

    public function test_logging_out_clears_the_session_and_allows_login_page_to_open(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/login')->assertOk();
    }

    public function test_invalid_credentials_return_to_login_with_an_error(): void
    {
        User::factory()->create([
            'email' => 'client@example.test',
            'password' => 'password123',
            'role' => 'client',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'client@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_vendor_can_sign_in_but_cannot_open_client_dashboard(): void
    {
        User::factory()->create([
            'email' => 'vendor@example.test',
            'password' => 'password123',
            'role' => 'vendor',
        ]);

        $this->post('/login', [
            'email' => 'vendor@example.test',
            'password' => 'password123',
        ])->assertRedirect('/');

        $this->get('/klien/dashboard')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_client_dashboard(): void
    {
        $this->get('/klien/dashboard')
            ->assertRedirect('/login');
    }

    public function test_dashboard_renders_a_post_logout_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'client']))
            ->get('/klien/dashboard')
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false);
    }
}
