<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien saat tagihan acara otomatis diterbitkan.
 */
class InvoiceIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amountFmt = number_format($this->invoice->transfer_total, 0, ',', '.');
        $dueFmt = $this->invoice->due_at?->format('d/m/Y H:i') ?? '-';

        return [
            'invoice_id' => $this->invoice->id,
            'event_id' => $this->invoice->event_id,
            'invoice_number' => $this->invoice->invoice_number,
            'transfer_total' => $this->invoice->transfer_total,
            'due_at' => $this->invoice->due_at?->toIso8601String(),
            'message' => "Tagihan {$this->invoice->invoice_number} telah diterbitkan sebesar Rp {$amountFmt}. Silakan lakukan pembayaran sebelum {$dueFmt} WIB.",
        ];
    }
}
