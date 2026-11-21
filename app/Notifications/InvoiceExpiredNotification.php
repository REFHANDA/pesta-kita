<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien saat tagihan kedaluwarsa karena tidak dibayar hingga batas waktu (due_at).
 */
class InvoiceExpiredNotification extends Notification
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
        return [
            'invoice_id' => $this->invoice->id,
            'event_id' => $this->invoice->event_id,
            'invoice_number' => $this->invoice->invoice_number,
            'message' => "Tagihan {$this->invoice->invoice_number} telah kedaluwarsa karena melewati batas waktu pembayaran 48 jam. Pesanan dan tanggal venue telah dibatalkan.",
        ];
    }
}
