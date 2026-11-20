<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien saat pembayaran divalidasi dan nota lunas terbit.
 */
class PaymentValidatedNotification extends Notification
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
            'amount' => $this->invoice->transfer_total,
            'message' => "Pembayaran untuk tagihan {$this->invoice->invoice_number} telah divalidasi. Acara Anda kini terkonfirmasi dan nota lunas telah terbit.",
        ];
    }
}
