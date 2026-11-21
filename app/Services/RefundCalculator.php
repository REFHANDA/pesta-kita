<?php

namespace App\Services;

use App\Enums\CancellationCause;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Admin\SettingController;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Kalkulator refund pembatalan (rancangan §4, Tahap B1). Murni: tidak menulis
 * database dan tidak membaca jam sistem; tanggal pengajuan selalu argumen.
 *
 * Rumus (aritmetika integer, G = grand total tanpa kode unik, P_i = harga penyedia):
 * - refund klien      = intdiv(G × p, 100) + kode unik (kode unik selalu kembali penuh)
 * - kompensasi_i      = P_i − intdiv(P_i × p, 100)
 * - pendapatan        = G − intdiv(G × p, 100) − Σ kompensasi_i (menyerap selisih pembulatan)
 * - penyebab vendor / force majeure → p = 100.
 */
class RefundCalculator
{
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * Tier refund aktif dari Pengaturan, diurutkan min_days menurun.
     *
     * @return array<int, array{min_days: int, percent: int}>
     */
    public function configuredTiers(): array
    {
        $tiers = Setting::get('refund_tiers', SettingController::DEFAULTS['refund_tiers']);

        return $this->normalizeTiers(is_array($tiers) ? $tiers : []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     * @return array<int, array{min_days: int, percent: int}>
     */
    public function normalizeTiers(array $tiers): array
    {
        $normalized = array_map(fn (array $tier): array => [
            'min_days' => (int) ($tier['min_days'] ?? 0),
            'percent' => (int) ($tier['percent'] ?? 0),
        ], array_values($tiers));

        usort($normalized, fn (array $a, array $b): int => $b['min_days'] <=> $a['min_days']);

        return $normalized;
    }

    /**
     * Sisa hari kalender (zona Asia/Jakarta, tanpa jam) dari tanggal pengajuan ke tanggal acara.
     */
    public function remainingDays(DateTimeInterface|string $eventDate, DateTimeInterface|string $requestedAt): int
    {
        $event = $this->calendarDate($eventDate);
        $requested = $this->calendarDate($requestedAt);

        return (int) $requested->diffInDays($event, false);
    }

    /**
     * Tier pertama (min_days menurun) dengan sisa hari >= min_days.
     *
     * @param  array<int, array<string, mixed>>  $tiers
     * @return array{min_days: int, percent: int}
     */
    public function selectTier(int $remainingDays, array $tiers): array
    {
        if ($remainingDays < 0) {
            throw new InvalidArgumentException('Tanggal acara sudah lewat; pembatalan tidak dapat dihitung.');
        }

        foreach ($this->normalizeTiers($tiers) as $tier) {
            if ($remainingDays >= $tier['min_days']) {
                return $tier;
            }
        }

        throw new InvalidArgumentException('Tabel tier refund tidak memiliki baris untuk sisa 0 hari.');
    }

    /**
     * Hitung refund, kompensasi per pesanan, dan pendapatan perusahaan.
     *
     * @param  array<int, array{order_item_id: int|null, label: string, provider_amount: int}>  $lines
     * @param  array<int, array<string, mixed>>  $tiers
     * @return array{
     *     refund_percent: int,
     *     refund_amount: int,
     *     unique_code_refund: int,
     *     refund_total: int,
     *     provider_compensation_total: int,
     *     company_income: int,
     *     lines: array<int, array{order_item_id: int|null, label: string, provider_amount: int, compensation: int}>,
     *     policy_snapshot: array<string, mixed>
     * }
     */
    public function calculate(
        int $grandTotal,
        int $uniqueCode,
        array $lines,
        CancellationCause $cause,
        DateTimeInterface|string $eventDate,
        DateTimeInterface|string $requestedAt,
        array $tiers,
        ?int $overridePercent = null,
        ?string $overrideReason = null,
    ): array {
        if ($grandTotal < 0 || $uniqueCode < 0) {
            throw new InvalidArgumentException('Nominal tagihan tidak boleh negatif.');
        }

        $normalizedTiers = $this->normalizeTiers($tiers);
        $remainingDays = $this->remainingDays($eventDate, $requestedAt);
        $tier = $this->selectTier($remainingDays, $normalizedTiers);

        if ($cause !== CancellationCause::Klien) {
            $percent = 100;
            $source = 'penyebab';
        } elseif ($overridePercent !== null) {
            if ($overridePercent < 0 || $overridePercent > 100) {
                throw new InvalidArgumentException('Persen refund harus 0–100.');
            }
            $percent = $overridePercent;
            $source = $overridePercent === $tier['percent'] ? 'tier' : 'override_admin';
        } else {
            $percent = $tier['percent'];
            $source = 'tier';
        }

        $refundAmount = intdiv($grandTotal * $percent, 100);

        $resultLines = [];
        $compensationTotal = 0;
        foreach ($lines as $line) {
            $providerAmount = (int) $line['provider_amount'];
            $compensation = $providerAmount - intdiv($providerAmount * $percent, 100);
            $compensationTotal += $compensation;
            $resultLines[] = [
                'order_item_id' => $line['order_item_id'] ?? null,
                'label' => (string) ($line['label'] ?? ''),
                'provider_amount' => $providerAmount,
                'compensation' => $compensation,
            ];
        }

        $companyIncome = $grandTotal - $refundAmount - $compensationTotal;

        if ($companyIncome < 0) {
            throw new InvalidArgumentException('Total harga penyedia melebihi grand total tagihan.');
        }

        return [
            'refund_percent' => $percent,
            'refund_amount' => $refundAmount,
            'unique_code_refund' => $uniqueCode,
            'refund_total' => $refundAmount + $uniqueCode,
            'provider_compensation_total' => $compensationTotal,
            'company_income' => $companyIncome,
            'lines' => $resultLines,
            'policy_snapshot' => [
                'tiers' => $normalizedTiers,
                'event_date' => $this->calendarDate($eventDate)->toDateString(),
                'requested_date' => $this->calendarDate($requestedAt)->toDateString(),
                'remaining_days' => $remainingDays,
                'selected_tier' => $tier,
                'tier_percent' => $tier['percent'],
                'percent' => $percent,
                'percent_source' => $source,
                'override_reason' => $source === 'override_admin' ? $overrideReason : null,
                'cause' => $cause->value,
                'grand_total' => $grandTotal,
                'unique_code' => $uniqueCode,
                'lines' => $resultLines,
                'refund_amount' => $refundAmount,
                'refund_total' => $refundAmount + $uniqueCode,
                'provider_compensation_total' => $compensationTotal,
                'company_income' => $companyIncome,
            ],
        ];
    }

    /**
     * Baris harga penyedia (P_i) dari pesanan aktif acara: terkonfirmasi atau selesai.
     *
     * @return array<int, array{order_item_id: int, label: string, provider_amount: int}>
     */
    public function linesForEvent(Event $event): array
    {
        return $event->orderItems()
            ->with('subject')
            ->whereIn('status', [OrderItemStatus::Terkonfirmasi, OrderItemStatus::Selesai])
            ->orderBy('id')
            ->get()
            ->map(fn (OrderItem $item): array => [
                'order_item_id' => (int) $item->id,
                'label' => (string) ($item->subject?->name ?? 'Pesanan #'.$item->id),
                'provider_amount' => $item->lineTotal(),
            ])
            ->all();
    }

    /**
     * Kalkulasi lengkap untuk acara/tagihan dengan tier dari Pengaturan.
     *
     * @return array<string, mixed>
     */
    public function forInvoice(
        Event $event,
        Invoice $invoice,
        CancellationCause $cause,
        DateTimeInterface|string $requestedAt,
        ?int $overridePercent = null,
        ?string $overrideReason = null,
        ?array $tiers = null,
    ): array {
        return $this->calculate(
            (int) $invoice->grand_total,
            (int) $invoice->unique_code,
            $this->linesForEvent($event),
            $cause,
            $event->event_date,
            $requestedAt,
            $tiers ?? $this->configuredTiers(),
            $overridePercent,
            $overrideReason,
        );
    }

    protected function calendarDate(DateTimeInterface|string $value): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE)->startOfDay();
        }

        return CarbonImmutable::parse($value, self::TIMEZONE)->startOfDay();
    }
}
