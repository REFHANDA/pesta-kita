<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Invoice;
use App\Notifications\InvoiceExpiredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command terjadwal untuk membatalkan tagihan belum_dibayar yang melewati due_at (48 jam).
 * Saat kedaluwarsa: status tagihan -> kedaluwarsa, pesanan dibatalkan, hold_key dikosongkan,
 * acara dibatalkan, audit log ditulis, dan klien dinotifikasi.
 */
class ExpireInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:expire';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Batalkan tagihan belum_dibayar yang telah melewati batas due_at, lepaskan slot dan batalkan pesanan.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa tagihan belum dibayar yang telah melewati due_at...');

        $expiredInvoices = Invoice::query()
            ->where('status', InvoiceStatus::BelumDibayar)
            ->where('due_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredInvoices as $invoice) {
            DB::transaction(function () use ($invoice) {
                /** @var Invoice $lockedInvoice */
                $lockedInvoice = Invoice::query()->where('id', $invoice->id)->lockForUpdate()->firstOrFail();

                if ($lockedInvoice->status !== InvoiceStatus::BelumDibayar || $lockedInvoice->due_at >= now()) {
                    return;
                }

                $lockedInvoice->update([
                    'status' => InvoiceStatus::Kedaluwarsa,
                ]);

                /** @var Event|null $event */
                $event = Event::query()->where('id', $lockedInvoice->event_id)->lockForUpdate()->first();

                if ($event) {
                    $event->update([
                        'status' => EventStatus::Dibatalkan,
                    ]);

                    // Batalkan seluruh item pesanan yang menahan slot/tanggal dan kosongkan hold_key
                    $event->orderItems()
                        ->whereIn('status', [
                            OrderItemStatus::Disetujui,
                            OrderItemStatus::MenungguReview,
                            OrderItemStatus::Draft,
                        ])
                        ->update([
                            'status' => OrderItemStatus::Dibatalkan,
                            'hold_key' => null,
                        ]);

                    $event->client?->notify(new InvoiceExpiredNotification($lockedInvoice));
                }

                AuditLog::create([
                    'actor_id' => null,
                    'action' => 'invoice_expired',
                    'subject_type' => $lockedInvoice->getMorphClass(),
                    'subject_id' => $lockedInvoice->id,
                    'from_status' => InvoiceStatus::BelumDibayar->value,
                    'to_status' => InvoiceStatus::Kedaluwarsa->value,
                    'reason' => "Tagihan {$lockedInvoice->invoice_number} kedaluwarsa karena tidak dibayar hingga {$lockedInvoice->due_at}.",
                    'ip' => null,
                ]);
            });

            $count++;
            $this->info("Tagihan {$invoice->invoice_number} telah kedaluwarsa.");
        }

        $this->info("Selesai. Total {$count} tagihan kedaluwarsa diproses.");

        return self::SUCCESS;
    }
}
