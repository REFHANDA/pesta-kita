<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * BE-1 §8: pembaruan profil dan kata sandi. Kepemilikan data selalu dari
 * authenticated user; email read-only; ganti sandi wajib verifikasi
 * kata sandi saat ini.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_update_profile_or_password(): void
    {
        $this->post('/profil', ['name' => 'Penyusup'])->assertRedirect('/login');
        $this->post('/profil/sandi', [])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_klien_can_update_name_and_phone(): void
    {
        $user = User::factory()->klien()->create([
            'name' => 'Nama Lama',
            'phone' => null,
        ]);

        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Rina Putri Baru',
            'phone' => '081299998888',
        ]);

        $response->assertRedirect('/klien/profil');
        $response->assertSessionHas('status', 'Profil berhasil diperbarui.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Rina Putri Baru',
            'phone' => '081299998888',
        ]);
    }

    public function test_email_cannot_be_changed_via_profile_update(): void
    {
        $user = User::factory()->klien()->create(['email' => 'asli@pestakita.test']);

        $this->actingAs($user)->post('/profil', [
            'name' => 'Rina Putri',
            'email' => 'pencuri@pestakita.test',
        ])->assertRedirect('/klien/profil');

        $this->assertSame('asli@pestakita.test', $user->fresh()->email);
    }

    public function test_profile_update_only_touches_authenticated_user(): void
    {
        $user = User::factory()->klien()->create(['name' => 'Pemilik Akun']);
        $other = User::factory()->klien()->create(['name' => 'Akun Lain']);

        $this->actingAs($user)->post('/profil', [
            'name' => 'Nama Diperbarui',
            'user_id' => $other->id,
            'id' => $other->id,
        ])->assertRedirect('/klien/profil');

        $this->assertSame('Nama Diperbarui', $user->fresh()->name);
        $this->assertSame('Akun Lain', $other->fresh()->name);
    }

    public function test_vendor_can_update_business_profile(): void
    {
        $user = User::factory()->vendor()->create();

        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Sari Wulandari',
            'phone' => '081200001111',
            'business_name' => 'Dapur Nusantara Baru',
            'category' => 'Dekorasi Pesta',
            'address' => 'Jl. Baru 1, Malang',
            'pic_name' => 'Sari Wulandari',
        ]);

        $response->assertRedirect('/vendor/profil');
        $this->assertDatabaseHas('vendor_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Dapur Nusantara Baru',
            'category' => 'Dekorasi Pesta',
            'address' => 'Jl. Baru 1, Malang',
            'pic_name' => 'Sari Wulandari',
        ]);
    }

    public function test_venue_can_update_business_profile(): void
    {
        $user = User::factory()->venue()->create();

        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Budi Santoso',
            'business_name' => 'Graha Melati Baru',
            'category' => 'Venue / Gedung pertemuan',
            'address' => 'Jl. Baru 2, Malang',
            'pic_name' => 'Budi Santoso',
        ]);

        $response->assertRedirect('/venue/profil');
        $this->assertDatabaseHas('venue_profiles', [
            'user_id' => $user->id,
            'business_name' => 'Graha Melati Baru',
            'category' => 'Venue / Gedung pertemuan',
            'address' => 'Jl. Baru 2, Malang',
        ]);
    }

    public function test_vendor_profile_update_requires_business_fields(): void
    {
        $user = User::factory()->vendor()->create();

        $response = $this->actingAs($user)->from('/vendor/profil')->post('/profil', [
            'name' => 'Sari Wulandari',
            'business_name' => '',
            'address' => '',
            'category' => '',
        ]);

        $response->assertRedirect('/vendor/profil');
        $response->assertSessionHasErrors(['business_name', 'address', 'category']);
    }

    public function test_admin_can_update_name_without_business_profile(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->post('/profil', [
            'name' => 'Admin Baru',
        ])->assertRedirect('/admin/profil');

        $this->assertSame('Admin Baru', $user->fresh()->name);
        $this->assertDatabaseCount('client_profiles', 0);
        $this->assertDatabaseCount('vendor_profiles', 0);
        $this->assertDatabaseCount('venue_profiles', 0);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->klien()->create();

        $response = $this->actingAs($user)->post('/profil/sandi', [
            'current_password' => 'password',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ]);

        $response->assertRedirect('/klien/profil');
        $response->assertSessionHas('status', 'Kata sandi berhasil diubah.');
        $this->assertTrue(Hash::check('kata-sandi-baru', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->klien()->create();

        $response = $this->actingAs($user)->from('/klien/profil')->post('/profil/sandi', [
            'current_password' => 'sandi-salah',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
        ]);

        $response->assertRedirect('/klien/profil');
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_validates_new_password(): void
    {
        $user = User::factory()->klien()->create();

        $response = $this->actingAs($user)->from('/klien/profil')->post('/profil/sandi', [
            'current_password' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'tidak-sama',
        ]);

        $response->assertRedirect('/klien/profil');
        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_profile_pages_render_for_each_role(): void
    {
        foreach (['admin', 'klien', 'vendor', 'venue'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->get("/{$role}/profil")
                ->assertOk()
                ->assertSee($user->email)
                ->assertSee('action="'.url('/profil').'"', false)
                ->assertSee('action="'.url('/profil/sandi').'"', false);
        }
    }
}
