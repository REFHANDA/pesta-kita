<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentValidationSlaReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public bool $isOverdue = false
    ) {}

    /**
     * @return list<string>
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
        $title = $this->isOverdue
            ? "PERINGATAN SLA: Bukti pembayaran {$invoice?->invoice_number} telah melewati batas 24 jam!"
            : "PENGINGAT SLA: Bukti pembayaran {$invoice?->invoice_number} mendekati batas SLA 24 jam.";

        return [
            'type' => 'payment_validation_sla_reminder',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'invoice_number' => $invoice?->invoice_number,
            'amount' => $this->payment->amount,
            'is_overdue' => $this->isOverdue,
            'title' => $title,
            'message' => 'Bukti transfer sebesar Rp '.number_format($this->payment->amount, 0, ',', '.')." diunggah pada {$this->payment->created_at?->format('d/m/Y H:i')} WIB dan memerlukan validasi admin.",
            'url' => route('admin.pembayaran.show', $this->payment->invoice_id),
        ];
    }
}
