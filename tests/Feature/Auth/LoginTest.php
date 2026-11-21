<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * BE-1 §8: login sesi web — sukses, redirect role DB (T1), pesan generik,
 * akun nonaktif, rate limit 5/menit, dan regenerasi session.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_render_for_guests(): void
    {
        foreach (['/login', '/klien/login', '/vendor/login', '/admin/login'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/register')->assertOk();
    }

    public function test_authenticated_users_are_redirected_from_login_pages_to_their_portal(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get('/login')->assertRedirect("/{$role}");
            $this->actingAs($user)->get('/register')->assertRedirect("/{$role}");
        }
    }

    public function test_users_can_login_and_are_redirected_by_database_role(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect("/{$role}");
            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
        }
    }

    public function test_login_redirect_ignores_role_tab_input(): void
    {
        // T1: tab role pada form hanya visual; server memakai role di database.
        $vendor = User::factory()->vendor()->create();

        $response = $this->post('/login', [
            'email' => $vendor->email,
            'password' => 'password',
            'role' => 'klien',
        ]);

        $response->assertRedirect('/vendor');
        $this->assertAuthenticatedAs($vendor);
    }

    public function test_login_regenerates_session(): void
    {
        $user = User::factory()->klien()->create();

        $this->get('/login');
        $sessionIdBefore = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotSame($sessionIdBefore, session()->getId());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->klien()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'kata-sandi-salah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'tidak-ada@pestakita.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_wrong_password_and_unknown_email_share_generic_message(): void
    {
        $user = User::factory()->klien()->create();

        $wrongPassword = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'kata-sandi-salah',
        ]);
        $wrongPassword->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

        $unknownEmail = $this->from('/login')->post('/login', [
            'email' => 'tidak-ada@pestakita.test',
            'password' => 'password',
        ]);
        $unknownEmail->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->klien()->nonaktif()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->klien()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'kata-sandi-salah',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $response->getSession()->get('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->klien()->create();
        $key = strtolower($user->email).'|127.0.0.1';

        for ($i = 0; $i < 4; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'kata-sandi-salah',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/klien');

        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_remember_me_sets_remember_token(): void
    {
        $user = User::factory()->klien()->create(['remember_token' => null]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect('/klien');

        $this->assertNotNull($user->fresh()->remember_token);
    }
}
