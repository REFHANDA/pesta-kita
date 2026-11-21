<?php

namespace App\Notifications;

use App\Enums\CancellationStatus;
use App\Models\Cancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien pada setiap perubahan status pengajuan pembatalan.
 */
class CancellationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Cancellation $cancellation,
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
        $eventId = $this->cancellation->event_id;
        $total = 'Rp '.number_format($this->cancellation->refundTotal(), 0, ',', '.');

        $message = match ($this->cancellation->status) {
            CancellationStatus::Diajukan => "Pengajuan pembatalan Acara #{$eventId} telah diterima dan menunggu tinjauan Admin.",
            CancellationStatus::Ditinjau => "Pengajuan pembatalan Acara #{$eventId} sedang ditinjau Admin.",
            CancellationStatus::Disetujui => "Pengajuan pembatalan Acara #{$eventId} disetujui. Refund {$total} ({$this->cancellation->refund_percent}% + kode unik) akan diproses.",
            CancellationStatus::RefundDiproses => "Refund {$total} untuk Acara #{$eventId} sedang diproses.",
            CancellationStatus::RefundSelesai => "Refund {$total} untuk Acara #{$eventId} telah ditransfer. Bukti refund dapat diunduh di detail acara.",
            CancellationStatus::Ditolak => "Pengajuan pembatalan Acara #{$eventId} ditolak: {$this->cancellation->decision_reason}",
        };

        return [
            'cancellation_id' => $this->cancellation->id,
            'event_id' => $eventId,
            'status' => $this->cancellation->status->value,
            'message' => $message,
        ];
    }
}
