<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\ProviderBankAccount;
use App\Models\User;
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
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Rekening masih menunggu verifikasi Admin.
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Menunggu,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Rekening sudah terverifikasi Admin.
     */
    public function terverifikasi(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Terverifikasi,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Rekening ditolak Admin beserta alasannya (Tahap G1).
     */
    public function ditolak(string $reason = 'Nomor rekening tidak sesuai buku tabungan.'): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Ditolak,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Rekening lama yang digantikan rekening terverifikasi terbaru.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Nonaktif,
        ]);
    }
}
