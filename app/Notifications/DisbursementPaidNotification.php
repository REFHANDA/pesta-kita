<?php

namespace App\Notifications;

use App\Enums\DisbursementType;
use App\Models\Disbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke penyedia: transfer pencairan telah dicatat. Tanpa nama klien.
 */
class DisbursementPaidNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Disbursement $disbursement,
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
        $eventId = $this->disbursement->orderItem?->event_id;
        $type = $this->disbursement->type === DisbursementType::Kompensasi ? 'kompensasi' : 'hak pesanan';
        $amount = 'Rp '.number_format((int) $this->disbursement->amount, 0, ',', '.');

        return [
            'disbursement_id' => $this->disbursement->id,
            'event_id' => $eventId,
            'message' => "Pencairan {$type} {$amount} untuk Acara #{$eventId} telah ditransfer.",
        ];
    }
}
