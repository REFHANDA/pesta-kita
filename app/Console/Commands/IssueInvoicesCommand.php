<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\InvoiceIssuer;
use Illuminate\Console\Command;

/**
 * Command terjadwal untuk menerbitkan tagihan otomatis bagi acara
 * yang seluruh pesanannya telah disetujui dan masa keberatan telah selesai.
 */
class IssueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:issue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terbitkan tagihan otomatis untuk acara yang memenuhi syarat (pesanan diputuskan dan masa keberatan selesai).';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceIssuer $issuer): int
    {
        $this->info('Memeriksa acara yang memenuhi syarat penerbitan tagihan...');

        $events = Event::query()
            ->where('status', EventStatus::Diajukan)
            ->get();

        $count = 0;
        foreach ($events as $event) {
            $invoice = $issuer->issueForEvent($event);
            if ($invoice) {
                $count++;
                $this->info("Tagihan {$invoice->invoice_number} berhasil diterbitkan untuk acara #{$event->id} ({$event->name}).");
            }
        }

        $this->info("Selesai. Total {$count} tagihan diterbitkan.");

        return Command::SUCCESS;
    }
}
