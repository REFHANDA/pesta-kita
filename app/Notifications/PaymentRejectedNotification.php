<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien saat bukti pembayaran ditolak Admin dengan catatan penolakan.
 */
class PaymentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public string $reason,
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
        $invoice = $this->payment->invoice;

        return [
            'payment_id' => $this->payment->id,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->invoice_number,
            'reason' => $this->reason,
            'message' => "Bukti pembayaran untuk tagihan {$invoice?->invoice_number} ditolak oleh Admin. Alasan: {$this->reason}. Silakan unggah ulang bukti yang benar.",
        ];
    }
}
