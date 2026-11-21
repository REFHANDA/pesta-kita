<?php

namespace Database\Factories;

use App\Models\CompanyBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyBankAccount>
 */
class CompanyBankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'account_number' => fake()->numerify('##########'),
            'account_name' => 'PT Pesta Kita',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Rekening perusahaan dinonaktifkan.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
