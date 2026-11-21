<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Klien,
            'phone' => null,
            'status' => UserStatus::Aktif,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Akun Klien beserta profil kosong.
     */
    public function klien(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Klien,
        ])->afterCreating(fn (User $user) => $user->clientProfile()->create([]));
    }

    /**
     * Akun Admin (tanpa profil usaha).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Admin,
        ]);
    }

    /**
     * Akun Vendor dengan profil usaha berstatus menunggu verifikasi (T2).
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Vendor,
        ])->afterCreating(fn (User $user) => $user->vendorProfile()->create([
            'business_name' => fake()->company(),
            'category' => 'Katering & F&B',
            'address' => fake()->address(),
            'verification_status' => VerificationStatus::Menunggu,
            'verified_at' => null,
        ]));
    }

    /**
     * Akun Pemilik Venue dengan profil usaha berstatus menunggu verifikasi (T2).
     */
    public function venue(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Venue,
        ])->afterCreating(fn (User $user) => $user->venueProfile()->create([
            'business_name' => fake()->company(),
            'category' => null,
            'address' => fake()->address(),
            'verification_status' => VerificationStatus::Menunggu,
            'verified_at' => null,
        ]));
    }

    /**
     * Akun dinonaktifkan; dapat digabung dengan state role lain.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Nonaktif,
        ]);
    }
}
