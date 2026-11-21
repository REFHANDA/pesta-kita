<?php

namespace Database\Factories;

use App\Enums\DisbursementStatus;
use App\Enums\DisbursementType;
use App\Models\Disbursement;
use App\Models\OrderItem;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disbursement>
 */
class DisbursementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'provider_type' => 'vendor_profile',
            'provider_id' => VendorProfile::factory(),
            'type' => DisbursementType::HakPesanan,
            'amount' => fake()->numberBetween(1, 20) * 1_000_000,
            'status' => DisbursementStatus::BelumCair,
            'paid_at' => null,
            'proof_media_id' => null,
            'recorded_by' => null,
        ];
    }
}
