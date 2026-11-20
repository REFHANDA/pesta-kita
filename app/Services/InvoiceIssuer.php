<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderObjectionStatus;
use App\Models\AuditLog;
use App\Models\DocumentCounter;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\VenueRoom;
use App\Notifications\InvoiceIssuedNotification;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Layanan penerbitan tagihan otomatis dan buat ulang tagihan sesuai aturan EMS:
 * - Diterbitkan jika semua pesanan diputuskan, masa keberatan selesai, dan tidak ada keberatan terbuka.
 * - Komponen: harga penyedia + komisi (10% dari pengaturan) + biaya kebersihan venue (jika ada) + kode unik (001-999).
 * - Batas bayar default 48 jam dari pengaturan payment_deadline_hours.
 */
class InvoiceIssuer
{
    /**
     * Periksa apakah acara telah memenuhi syarat penerbitan tagihan otomatis:
     * 1. Status acara adalah diajukan.
     * 2. Tidak ada pesanan berstatus menunggu_review.
     * 3. Memiliki setidaknya satu pesanan berstatus disetujui.
     * 4. Masa keberatan seluruh pesanan disetujui telah berakhir.
     * 5. Tidak ada keberatan terbuka (diajukan / ditinjau) pada pesanan disetujui.
     * 6. Belum memiliki tagihan aktif (belum_dibayar / menunggu_validasi / lunas).
     */
    public function canIssueForEvent(Event $event): bool
    {
        if ($event->status !== EventStatus::Diajukan) {
            return false;
        }

        // Cek apakah masih ada pesanan yang menunggu review admin
        $hasPendingReview = $event->orderItems()
            ->where('status', OrderItemStatus::MenungguReview)
            ->exists();

        if ($hasPendingReview) {
            return false;
        }

        $approvedItems = $event->orderItems()
            ->where('status', OrderItemStatus::Disetujui)
            ->get();

        if ($approvedItems->isEmpty()) {
            return false;
        }

        $now = now();
        $objectionWindowHours = (int) Setting::get('objection_window_hours', 24);

        foreach ($approvedItems as $item) {
            $deadline = $item->objection_deadline
                ?? ($item->approved_at ? Carbon::parse($item->approved_at)->addHours($objectionWindowHours) : null);

            // Bila masih dalam masa keberatan, tagihan belum boleh terbit
            if ($deadline && $now->lt($deadline)) {
                return false;
            }

            // Bila ada keberatan yang belum selesai diputuskan
            $hasOpenObjection = $item->objections()
                ->whereIn('status', [OrderObjectionStatus::Diajukan, OrderObjectionStatus::Ditinjau])
                ->exists();

            if ($hasOpenObjection) {
                return false;
            }
        }

        // Cek tagihan aktif yang sudah ada
        $hasActiveInvoice = Invoice::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [
                InvoiceStatus::BelumDibayar,
                InvoiceStatus::MenungguValidasi,
                InvoiceStatus::Lunas,
            ])
            ->exists();

        if ($hasActiveInvoice) {
            return false;
        }

        return true;
    }

    /**
     * Terbitkan tagihan baru untuk acara yang memenuhi syarat.
     * Seluruh operasi dibungkus DB::transaction dengan lockForUpdate.
     */
    public function issueForEvent(Event $event): ?Invoice
    {
        return retry(3, function () use ($event) {
            return DB::transaction(function () use ($event) {
                /** @var Event $lockedEvent */
                $lockedEvent = Event::query()->where('id', $event->id)->lockForUpdate()->firstOrFail();

                if (! $this->canIssueForEvent($lockedEvent)) {
                    return null;
                }

                $approvedItems = $lockedEvent->orderItems()
                    ->where('status', OrderItemStatus::Disetujui)
                    ->lockForUpdate()
                    ->get();

                $invoice = $this->createInvoiceWithItems($lockedEvent, $approvedItems, 1);

                // Perbarui status acara menjadi menunggu_pembayaran
                $lockedEvent->update([
                    'status' => EventStatus::MenungguPembayaran,
                ]);

                AuditLog::create([
                    'actor_id' => Auth::id(),
                    'action' => 'invoice_issued',
                    'subject_type' => $invoice->getMorphClass(),
                    'subject_id' => $invoice->id,
                    'from_status' => null,
                    'to_status' => InvoiceStatus::BelumDibayar->value,
                    'reason' => "Penerbitan tagihan {$invoice->invoice_number} otomatis setelah masa keberatan selesai.",
                    'ip' => request()?->ip(),
                ]);

                $lockedEvent->client?->notify(new InvoiceIssuedNotification($invoice));

                return $invoice;
            });
        }, 50);
    }

    /**
     * Buat ulang tagihan (hanya jika tagihan lama belum_dibayar).
     * Versi lama diubah menjadi dibatalkan, versi baru diterbitkan dengan nomor/versi baru.
     */
    public function reissue(Invoice $oldInvoice, User $admin): Invoice
    {
        return retry(3, function () use ($oldInvoice, $admin) {
            return DB::transaction(function () use ($oldInvoice, $admin) {
                /** @var Invoice $lockedOld */
                $lockedOld = Invoice::query()->where('id', $oldInvoice->id)->lockForUpdate()->firstOrFail();

                if ($lockedOld->status !== InvoiceStatus::BelumDibayar) {
                    throw new RuntimeException('Hanya tagihan berstatus belum_dibayar yang dapat dibuat ulang.');
                }

                /** @var Event $lockedEvent */
                $lockedEvent = Event::query()->where('id', $lockedOld->event_id)->lockForUpdate()->firstOrFail();

                // Batalkan tagihan lama
                $lockedOld->update([
                    'status' => InvoiceStatus::Dibatalkan,
                ]);

                $approvedItems = $lockedEvent->orderItems()
                    ->where('status', OrderItemStatus::Disetujui)
                    ->lockForUpdate()
                    ->get();

                $newVersion = ((int) $lockedOld->version) + 1;
                $newInvoice = $this->createInvoiceWithItems($lockedEvent, $approvedItems, $newVersion);

                AuditLog::create([
                    'actor_id' => $admin->id,
                    'action' => 'invoice_reissued',
                    'subject_type' => $newInvoice->getMorphClass(),
                    'subject_id' => $newInvoice->id,
                    'from_status' => $lockedOld->invoice_number,
                    'to_status' => $newInvoice->invoice_number,
                    'reason' => "Admin membuat ulang tagihan versi {$newVersion} menggantikan {$lockedOld->invoice_number}.",
                    'ip' => request()?->ip(),
                ]);

                $lockedEvent->client?->notify(new InvoiceIssuedNotification($newInvoice));

                return $newInvoice;
            });
        }, 50);
    }

    /**
     * Hitung subtotal, komisi, biaya kebersihan, dan buat record invoice beserta baris rincian invoice_items.
     *
     * @param  Collection<int, OrderItem>  $approvedItems
     */
    private function createInvoiceWithItems(Event $lockedEvent, $approvedItems, int $version): Invoice
    {
        $subtotalProvider = (int) $approvedItems->sum(fn (OrderItem $item) => $item->lineTotal());
        $totalCommission = (int) $approvedItems->sum('commission');

        // Hitung biaya kebersihan venue jika ada ruangan venue yang disewa
        $cleaningFee = 0;
        $venueItem = $approvedItems->first(function (OrderItem $item) {
            return $item->subject_type === 'venue_room' || $item->subject instanceof VenueRoom;
        });

        $venue = null;
        if ($venueItem) {
            $venue = $venueItem->subject instanceof VenueRoom
                ? $venueItem->subject->venue
                : VenueRoom::with('venue')->find($venueItem->subject_id)?->venue;

            if ($venue && $venue->cleaning_fee > 0) {
                $cleaningFee = (int) $venue->cleaning_fee;
            }
        }

        $grandTotal = $subtotalProvider + $totalCommission + $cleaningFee;
        $uniqueCode = $this->generateUniqueCode();
        $transferTotal = $grandTotal + $uniqueCode;

        $deadlineHours = (int) Setting::get('payment_deadline_hours', 48);
        $dueAt = now()->addHours($deadlineHours);
        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = Invoice::create([
            'event_id' => $lockedEvent->id,
            'invoice_number' => $invoiceNumber,
            'version' => $version,
            'subtotal_provider' => $subtotalProvider,
            'total_commission' => $totalCommission,
            'cleaning_fee' => $cleaningFee,
            'grand_total' => $grandTotal,
            'unique_code' => $uniqueCode,
            'transfer_total' => $transferTotal,
            'due_at' => $dueAt,
            'status' => InvoiceStatus::BelumDibayar,
            'issued_at' => now(),
        ]);

        // Buat baris rincian invoice_items
        foreach ($approvedItems as $item) {
            $subjectName = $item->subject?->name ?? 'Layanan';
            $qty = (int) ($item->quantity ?? 1);
            $descSuffix = $qty > 1 ? " x{$qty}" : '';

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'order_item_id' => $item->id,
                'type' => InvoiceItemType::HargaPenyedia,
                'description' => "Harga penyedia: {$subjectName}{$descSuffix}",
                'amount' => $item->lineTotal(),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'order_item_id' => $item->id,
                'type' => InvoiceItemType::Komisi,
                'description' => "Komisi platform (10%): {$subjectName}{$descSuffix}",
                'amount' => (int) $item->commission,
            ]);
        }

        if ($cleaningFee > 0) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'order_item_id' => null,
                'type' => InvoiceItemType::Kebersihan,
                'description' => 'Biaya kebersihan venue ('.($venue?->name ?? 'Venue').')',
                'amount' => $cleaningFee,
            ]);
        }

        return $invoice;
    }

    /**
     * Dapatkan nomor dokumen berikutnya secara berurutan dan aman dari balapan (race condition):
     * Menggunakan tabel counter periodik (YYYY/MM) dengan lockForUpdate di dalam transaksi database.
     */
    public function getNextDocumentNumber(?DateTimeInterface $date = null, ?string $excludeNumber = null): string
    {
        $d = $date ? Carbon::parse($date) : now();
        $period = sprintf('%s/%s', $d->format('Y'), $d->format('m'));
        $prefix = "PK/{$period}/";

        $generate = function () use ($period, $prefix, $excludeNumber) {
            /** @var DocumentCounter|null $counter */
            $counter = DocumentCounter::query()
                ->where('type', 'PK')
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                // Inisialisasi awal counter dari nilai tertinggi pada data existing
                $latestInv = Invoice::query()->where('invoice_number', 'like', $prefix.'%')->orderByDesc('invoice_number')->value('invoice_number');
                $latestRcpt = Invoice::query()->where('receipt_number', 'like', $prefix.'%')->orderByDesc('receipt_number')->value('receipt_number');

                $maxSeq = 0;
                foreach ([$latestInv, $latestRcpt] as $val) {
                    if ($val && preg_match('/(\d{4})$/', $val, $m)) {
                        $maxSeq = max($maxSeq, (int) $m[1]);
                    }
                }

                $counter = DocumentCounter::firstOrCreate(
                    ['type' => 'PK', 'period' => $period],
                    ['current_number' => $maxSeq]
                );

                $counter = DocumentCounter::query()
                    ->where('id', $counter->id)
                    ->lockForUpdate()
                    ->first();
            }

            $counter->increment('current_number');
            $candidate = sprintf('%s%04d', $prefix, $counter->current_number);

            while (
                ($excludeNumber && $candidate === $excludeNumber)
                || Invoice::query()->where('invoice_number', $candidate)->exists()
                || Invoice::query()->where('receipt_number', $candidate)->exists()
            ) {
                $counter->increment('current_number');
                $candidate = sprintf('%s%04d', $prefix, $counter->current_number);
            }

            return $candidate;
        };

        if (DB::transactionLevel() > 0) {
            return $generate();
        }

        return DB::transaction($generate);
    }

    /**
     * Generate format nomor tagihan: PK/YYYY/MM/NNNN (contoh PK/2026/11/0001).
     * Aman dari kondisi balapan melalui locking counter transaksi.
     */
    public function generateInvoiceNumber(?DateTimeInterface $date = null): string
    {
        return $this->getNextDocumentNumber($date);
    }

    /**
     * Generate nomor nota lunas: PK/YYYY/MM/NNNN (contoh PK/2026/11/0002).
     * Wajib unik (unique index), counter dalam transaksi, dan tidak boleh sama dengan nomor tagihan.
     */
    public function generateReceiptNumber(?Invoice $invoice = null, ?DateTimeInterface $date = null): string
    {
        return $this->getNextDocumentNumber($date, $invoice?->invoice_number);
    }

    /**
     * Generate kode unik 3-digit (001-999) acak yang tidak sama dengan
     * tagihan lain yang masih belum_dibayar atau menunggu_validasi.
     */
    public function generateUniqueCode(): int
    {
        if (! Setting::get('unique_code_enabled', true)) {
            return 0;
        }

        $activeCodes = Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::BelumDibayar->value,
                InvoiceStatus::MenungguValidasi->value,
            ])
            ->pluck('unique_code')
            ->all();

        $activeLookup = array_flip($activeCodes);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $candidate = random_int(1, 999);
            if (! isset($activeLookup[$candidate])) {
                return $candidate;
            }
        }

        for ($code = 1; $code <= 999; $code++) {
            if (! isset($activeLookup[$code])) {
                return $code;
            }
        }

        return random_int(1, 999);
    }
}
