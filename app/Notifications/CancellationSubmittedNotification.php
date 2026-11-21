<?php

namespace App\Notifications;

use App\Models\Cancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Admin: klien mengajukan pembatalan acara yang sudah lunas.
 */
class CancellationSubmittedNotification extends Notification
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
        return [
            'cancellation_id' => $this->cancellation->id,
            'event_id' => $this->cancellation->event_id,
            'message' => "Pengajuan pembatalan baru untuk Acara #{$this->cancellation->event_id} perlu ditinjau.",
        ];
    }
}
