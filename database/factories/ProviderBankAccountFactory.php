<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\ProviderBankAccount;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderBankAccount>
 */
class ProviderBankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'owner_type' => 'vendor_profile',
            'owner_id' => VendorProfile::factory(),
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'account_number' => fake()->numerify('##########'),
            'account_name' => fake()->name(),
            'verification_status' => VerificationStatus::Menunggu,
        ];
    }

    /**
     * Rekening masih menunggu verifikasi Admin.
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Menunggu,
        ]);
    }

    /**
     * Rekening sudah terverifikasi Admin.
     */
    public function terverifikasi(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Terverifikasi,
        ]);
    }
}
