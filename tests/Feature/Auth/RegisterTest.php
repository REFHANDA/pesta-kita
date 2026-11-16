<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * BE-1 §8: registrasi publik klien/vendor/venue — role terkunci (admin
 * ditolak server), validasi, profil per role, status T2, dan rollback
 * transaction bila pembuatan profil gagal.
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload registrasi valid untuk role tertentu.
     *
     * @return array<string, mixed>
     */
    private function payload(string $role, array $overrides = []): array
    {
        $base = [
            'role' => $role,
            'name' => 'Rina Putri',
            'email' => 'rina@pestakita.test',
            'phone' => '081234567890',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ];

        if (in_array($role, ['vendor', 'venue'], true)) {
            $base['business_name'] = 'Dapur Nusantara';
            $base['address'] = 'Jl. Ijen 8, Malang';
        }

        if ($role === 'vendor') {
            $base['category'] = 'Katering & F&B';
        }

        return array_merge($base, $overrides);
    }

    public function test_klien_can_register_and_gets_client_profile(): void
    {
        $response = $this->post('/register', $this->payload('klien'));

        $response->assertRedirect('/klien');

        $user = User::where('email', 'rina@pestakita.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::Klien, $user->role);
        $this->assertSame(UserStatus::Aktif, $user->status);
        $this->assertTrue(Hash::check('kata-sandi-baru', $user->password));
        $this->assertDatabaseHas('client_profiles', ['user_id' => $user->id]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_vendor_can_register_with_pending_business_verification(): void
    {
        $response = $this->post('/register', $this->payload('vendor'));

        $response->assertRedirect('/vendor/verifikasi');

        $user = User::where('email', 'rina@pestakita.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::Vendor, $user->role);
        // T2: akun langsung aktif, verifikasi bisnis menunggu.
        $this->assertSame(UserStatus::Aktif, $user->status);
        $this->assertDatabaseHas('vendor_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Dapur Nusantara',
            'category' => 'Katering & F&B',
            'address' => 'Jl. Ijen 8, Malang',
            'verification_status' => VerificationStatus::Menunggu->value,
            'verified_at' => null,
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_venue_can_register_with_pending_business_verification(): void
    {
        $response = $this->post('/register', $this->payload('venue'));

        $response->assertRedirect('/venue/verifikasi');

        $user = User::where('email', 'rina@pestakita.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::Venue, $user->role);
        $this->assertSame(UserStatus::Aktif, $user->status);
        $this->assertDatabaseHas('venue_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Dapur Nusantara',
            'category' => null,
            'address' => 'Jl. Ijen 8, Malang',
            'verification_status' => VerificationStatus::Menunggu->value,
            'verified_at' => null,
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_role_cannot_be_registered(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('admin'));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_unknown_role_cannot_be_registered(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('superadmin'));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->klien()->create(['email' => 'rina@pestakita.test']);

        $response = $this->from('/register')->post('/register', $this->payload('klien'));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_must_be_confirmed(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('klien', [
            'password_confirmation' => 'berbeda-sama-sekali',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_must_be_at_least_eight_characters(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('klien', [
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_vendor_registration_requires_business_fields(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('vendor', [
            'business_name' => '',
            'address' => '',
            'category' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['business_name', 'address', 'category']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_vendor_category_must_be_one_of_the_form_options(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('vendor', [
            'category' => 'Kategori Tidak Ada',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_venue_registration_requires_business_fields(): void
    {
        $response = $this->from('/register')->post('/register', $this->payload('venue', [
            'business_name' => '',
            'address' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['business_name', 'address']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rolls_back_user_when_profile_creation_fails(): void
    {
        // Simulasikan kegagalan pembuatan profil: tabel profil dihapus.
        Schema::dropIfExists('vendor_profiles');

        $response = $this->post('/register', $this->payload('vendor'));

        $response->assertStatus(500);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_authenticated_users_cannot_open_register(): void
    {
        $user = User::factory()->klien()->create();

        $this->actingAs($user)->get('/register')->assertRedirect('/klien');
        $this->actingAs($user)->post('/register', $this->payload('klien', [
            'email' => 'lain@pestakita.test',
        ]))->assertRedirect('/klien');

        $this->assertDatabaseCount('users', 1);
    }
}
