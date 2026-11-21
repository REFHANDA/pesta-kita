<?php

namespace App\Notifications;

use App\Models\Disbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke penyedia: acara dibatalkan setelah lunas dan kompensasi masuk
 * antrean pencairan. Hanya "Acara #ID", tanpa nama klien.
 */
class CompensationRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Disbursement $disbursement,
        public int $eventId,
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
        $amount = 'Rp '.number_format((int) $this->disbursement->amount, 0, ',', '.');

        return [
            'disbursement_id' => $this->disbursement->id,
            'event_id' => $this->eventId,
            'message' => "Acara #{$this->eventId} dibatalkan klien. Kompensasi {$amount} masuk antrean pencairan.",
        ];
    }
}
