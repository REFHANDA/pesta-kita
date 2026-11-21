<?php

namespace Tests\Feature\Services;

use App\Enums\CancellationCause;
use App\Http\Controllers\Admin\SettingController;
use App\Models\Setting;
use App\Services\RefundCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RefundCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private const G = 34_500_000;

    private const UNIQUE = 123;

    /**
     * @return array<int, array{order_item_id: int, label: string, provider_amount: int}>
     */
    private function referenceLines(): array
    {
        return [
            ['order_item_id' => 1, 'label' => 'Ruang venue', 'provider_amount' => 20_000_000],
            ['order_item_id' => 2, 'label' => 'Katering', 'provider_amount' => 10_000_000],
        ];
    }

    /**
     * @return array<int, array{min_days: int, percent: int}>
     */
    private function tiers(): array
    {
        return SettingController::DEFAULTS['refund_tiers'];
    }

    private function calc(): RefundCalculator
    {
        return new RefundCalculator;
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function boundaryProvider(): array
    {
        return [
            '30 hari' => [30, 90], '29 hari' => [29, 70], '14 hari' => [14, 70], '13 hari' => [13, 50],
            '7 hari' => [7, 50], '6 hari' => [6, 25], '3 hari' => [3, 25], '2 hari' => [2, 0], '0 hari' => [0, 0],
        ];
    }

    #[DataProvider('boundaryProvider')]
    public function test_batas_hari_memilih_tier_yang_benar(int $days, int $expectedPercent): void
    {
        $requested = '2026-12-01';
        $eventDate = now()->parse($requested)->addDays($days)->toDateString();

        $result = $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, $eventDate, $requested, $this->tiers());

        $this->assertSame($days, $result['policy_snapshot']['remaining_days']);
        $this->assertSame($expectedPercent, $result['refund_percent']);
        $this->assertInvariant($result, self::G);
    }

    /**
     * @return array<string, array{int, int, int, int, int, int}>
     */
    public static function referenceProvider(): array
    {
        // [days, p, refund total, kompensasi venue, kompensasi katering, pendapatan]
        return [
            '90%' => [30, 90, 31_050_123, 2_000_000, 1_000_000, 450_000],
            '70%' => [14, 70, 24_150_123, 6_000_000, 3_000_000, 1_350_000],
            '50%' => [7, 50, 17_250_123, 10_000_000, 5_000_000, 2_250_000],
            '25%' => [3, 25, 8_625_123, 15_000_000, 7_500_000, 3_375_000],
            '0%' => [0, 0, 123, 20_000_000, 10_000_000, 4_500_000],
        ];
    }

    #[DataProvider('referenceProvider')]
    public function test_skenario_acuan(int $days, int $p, int $refund, int $compVenue, int $compCatering, int $income): void
    {
        $result = $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, now()->parse('2026-12-01')->addDays($days), '2026-12-01', $this->tiers());

        $this->assertSame($p, $result['refund_percent']);
        $this->assertSame($refund, $result['refund_total']);
        $this->assertSame($refund - self::UNIQUE, $result['refund_amount']);
        $this->assertSame(self::UNIQUE, $result['unique_code_refund']);
        $this->assertSame($compVenue, $result['lines'][0]['compensation']);
        $this->assertSame($compCatering, $result['lines'][1]['compensation']);
        $this->assertSame($compVenue + $compCatering, $result['provider_compensation_total']);
        $this->assertSame($income, $result['company_income']);
        $this->assertInvariant($result, self::G);
    }

    public function test_vendor_dan_force_majeure_refund_penuh_tanpa_kompensasi(): void
    {
        foreach ([CancellationCause::Vendor, CancellationCause::ForceMajeure] as $cause) {
            $result = $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), $cause, '2026-12-02', '2026-12-01', $this->tiers());

            $this->assertSame(100, $result['refund_percent']);
            $this->assertSame(34_500_123, $result['refund_total']);
            $this->assertSame(0, $result['provider_compensation_total']);
            $this->assertSame(0, $result['company_income']);
            $this->assertSame('penyebab', $result['policy_snapshot']['percent_source']);
            $this->assertInvariant($result, self::G);
        }
    }

    public function test_skenario_pembulatan(): void
    {
        $lines = [['order_item_id' => 9, 'label' => 'Layanan', 'provider_amount' => 1_000_001]];

        $result = $this->calc()->calculate(1_100_001, 0, $lines, CancellationCause::Klien, '2026-12-21', '2026-12-01', $this->tiers());

        $this->assertSame(70, $result['refund_percent']);
        $this->assertSame(770_000, $result['refund_total']);
        $this->assertSame(300_001, $result['provider_compensation_total']);
        $this->assertSame(30_000, $result['company_income']);
        $this->assertInvariant($result, 1_100_001);
    }

    public function test_override_persen_ditandai_pada_snapshot(): void
    {
        $result = $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, '2026-12-15', '2026-12-01', $this->tiers(), 80, 'Kebijakan khusus');

        $this->assertSame(80, $result['refund_percent']);
        $this->assertSame(70, $result['policy_snapshot']['tier_percent']);
        $this->assertSame('override_admin', $result['policy_snapshot']['percent_source']);
        $this->assertSame('Kebijakan khusus', $result['policy_snapshot']['override_reason']);
        $this->assertInvariant($result, self::G);
    }

    public function test_tier_dari_pengaturan_langsung_terpakai(): void
    {
        Setting::set('refund_tiers', [
            ['min_days' => 10, 'percent' => 60],
            ['min_days' => 0, 'percent' => 10],
        ]);

        $calc = $this->calc();
        $result = $calc->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, '2026-12-31', '2026-12-01', $calc->configuredTiers());

        $this->assertSame(60, $result['refund_percent']);
        $this->assertSame([['min_days' => 10, 'percent' => 60], ['min_days' => 0, 'percent' => 10]], $result['policy_snapshot']['tiers']);

        $result = $calc->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, '2026-12-05', '2026-12-01', $calc->configuredTiers());
        $this->assertSame(10, $result['refund_percent']);
    }

    public function test_sisa_hari_kalender_zona_jakarta_tanpa_jam(): void
    {
        // 16:30 UTC = 23:30 WIB tanggal 1; 18:00 UTC = 01:00 WIB tanggal 2.
        $this->assertSame(2, $this->calc()->remainingDays('2026-12-03', now()->parse('2026-12-01 16:30:00', 'UTC')));
        $this->assertSame(1, $this->calc()->remainingDays('2026-12-03', now()->parse('2026-12-01 18:00:00', 'UTC')));
        $this->assertSame(0, $this->calc()->remainingDays('2026-12-03', '2026-12-03 23:59:00'));
    }

    public function test_tanggal_lewat_ditolak(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, '2026-11-30', '2026-12-01', $this->tiers());
    }

    public function test_kalkulator_tidak_menulis_database(): void
    {
        $before = \DB::table('settings')->count();
        $this->calc()->calculate(self::G, self::UNIQUE, $this->referenceLines(), CancellationCause::Klien, '2026-12-31', '2026-12-01', $this->tiers());
        $this->assertSame($before, \DB::table('settings')->count());
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function assertInvariant(array $result, int $grandTotal): void
    {
        $this->assertSame($grandTotal, $result['refund_amount'] + $result['provider_compensation_total'] + $result['company_income']);
    }
}
