<?php

namespace Database\Factories;

use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;
use App\Models\Cancellation;
use App\Models\Event;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cancellation>
 */
class CancellationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grandTotal = 10_000_000;
        $refundPercent = 70;
        $refundAmount = (int) ($grandTotal * ($refundPercent / 100));

        return [
            'event_id' => Event::factory(),
            'invoice_id' => Invoice::factory(),
            'reason' => fake()->paragraph(),
            'cause' => CancellationCause::Klien,
            'refund_percent' => $refundPercent,
            'refund_amount' => $refundAmount,
            'unique_code_refund' => 342,
            'provider_compensation_total' => 2_400_000,
            'company_income' => 600_000,
            'policy_snapshot' => [
                'tier' => '14-29 hari',
                'percent' => 70,
                'calculated_at' => now()->toIso8601String(),
            ],
            'status' => CancellationStatus::Diajukan,
            'refund_proof_media_id' => null,
            'reviewed_by' => null,
            'decision_reason' => null,
        ];
    }
}
