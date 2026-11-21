<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\VenueRoom;
use Illuminate\Support\Collection;

/**
 * Estimasi biaya yang dibayar Klien untuk satu item (Tahap F2).
 *
 * Rumus disalin persis dari jalur tagihan yang sudah ada:
 * - Komisi: `OrderReviewController::approve` →
 *   `intdiv(lineTotal × commission_percent + 50, 100)` dengan persen dari
 *   `Setting::get('commission_percent', 10)` (pembulatan setengah ke atas).
 * - Biaya kebersihan: `InvoiceIssuer::createInvoiceWithItems` → satu nominal
 *   `venues.cleaning_fee` per acara (null/0 = tidak dikenakan), tidak
 *   dikalikan jumlah/hari dan tidak dikenai komisi.
 *
 * Kelas ini murni: hanya membaca `settings`, tanpa menulis database, dan
 * tidak dipakai oleh `OrderReviewController`/`InvoiceIssuer` (keduanya tetap
 * sumber kebenaran). Kesamaan rumus dijaga tes pagar F2.
 */
class ClientCostEstimator
{
    /**
     * Persen komisi bawaan bila Pengaturan belum menyimpan nilainya.
     */
    public const DEFAULT_COMMISSION_PERCENT = 10;

    /**
     * Persen komisi aktif dari Pengaturan.
     */
    public function commissionPercent(): int
    {
        return (int) Setting::get('commission_percent', self::DEFAULT_COMMISSION_PERCENT);
    }

    /**
     * Komisi untuk satu baris pesanan (sudah dikalikan jumlah).
     */
    public function commission(int $lineTotal): int
    {
        return intdiv($lineTotal * $this->commissionPercent() + 50, 100);
    }

    /**
     * Estimasi biaya item katalog (layanan/peralatan): harga + komisi.
     *
     * @return array{price: int, commission: int, cleaning_fee: int, estimate: int}
     */
    public function catalogItem(int $providerPrice, int $quantity = 1): array
    {
        $lineTotal = $providerPrice * max(1, $quantity);
        $commission = $this->commission($lineTotal);

        return [
            'price' => $lineTotal,
            'commission' => $commission,
            'cleaning_fee' => 0,
            'estimate' => $lineTotal + $commission,
        ];
    }

    /**
     * Estimasi biaya venue: harga sewa + komisi + biaya kebersihan venue.
     *
     * @return array{price: int, commission: int, cleaning_fee: int, estimate: int}
     */
    public function venue(int $rentalPrice, ?int $cleaningFee = null, int $quantity = 1): array
    {
        $lineTotal = $rentalPrice * max(1, $quantity);
        $commission = $this->commission($lineTotal);
        $cleaning = max(0, (int) $cleaningFee);

        return [
            'price' => $lineTotal,
            'commission' => $commission,
            'cleaning_fee' => $cleaning,
            'estimate' => $lineTotal + $commission + $cleaning,
        ];
    }

    /**
     * Estimasi biaya satu acara untuk kartu "Estimasi Biaya" (Tahap G1).
     *
     * - Bila acara punya tagihan aktif (belum_dibayar/menunggu_validasi/lunas):
     *   angka = `grand_total` tagihan (tanpa kode unik), sumber kebenaran.
     * - Bila belum: Σ (harga penyedia + komisi) + biaya kebersihan venue sekali
     *   per acara. Komisi yang sudah terkunci (> 0) dipakai apa adanya; bila
     *   belum, dihitung dengan `commission()`.
     *
     * @param  Collection<int, OrderItem>|null  $items  Himpunan pesanan yang dihitung (bawaan: seluruh pesanan acara).
     * @return array{amount: int, invoice_number: string|null}
     */
    public function forEvent(Event $event, ?Collection $items = null): array
    {
        $invoice = $event->invoices()
            ->whereIn('status', [
                InvoiceStatus::BelumDibayar,
                InvoiceStatus::MenungguValidasi,
                InvoiceStatus::Lunas,
            ])
            ->latest('id')
            ->first();

        if ($invoice !== null) {
            return [
                'amount' => (int) $invoice->grand_total,
                'invoice_number' => $invoice->invoice_number,
            ];
        }

        $items ??= $event->orderItems;

        $total = 0;
        foreach ($items as $item) {
            $lineTotal = $item->lineTotal();
            $locked = (int) $item->commission;

            $total += $lineTotal + ($locked > 0 ? $locked : $this->commission($lineTotal));
        }

        return [
            'amount' => $total + $this->cleaningFeeFor($items),
            'invoice_number' => null,
        ];
    }

    /**
     * Biaya kebersihan sekali per acara dari venue pesanan venue_room pertama
     * (sama dengan `InvoiceIssuer`), hanya bila `cleaning_fee` > 0.
     *
     * @param  Collection<int, OrderItem>  $items
     */
    protected function cleaningFeeFor(Collection $items): int
    {
        $venueItem = $items->first(
            fn (OrderItem $item): bool => $item->subject_type === 'venue_room' || $item->subject instanceof VenueRoom
        );

        if ($venueItem === null) {
            return 0;
        }

        $venue = $venueItem->subject instanceof VenueRoom
            ? $venueItem->subject->venue
            : VenueRoom::with('venue')->find($venueItem->subject_id)?->venue;

        return $venue !== null && $venue->cleaning_fee > 0 ? (int) $venue->cleaning_fee : 0;
    }
}
