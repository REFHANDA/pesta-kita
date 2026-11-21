<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi rundown berubah (Tahap C6). Penerima penyedia hanya melihat
 * "Acara #ID" tanpa nama atau kontak klien; penerima klien tidak memuat
 * kontak vendor. Penggabungan notifikasi belum dibaca diatur pemanggil.
 */
class RundownUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $audience  vendor|venue (dari klien) atau klien (dari vendor)
     */
    public function __construct(
        public Event $event,
        public string $audience,
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
        $message = $this->audience === 'klien'
            ? "Rundown Acara #{$this->event->id} diperbarui oleh vendor."
            : "Rundown Acara #{$this->event->id} diperbarui oleh klien.";

        $url = match ($this->audience) {
            'vendor' => route('vendor.rundown.show', $this->event),
            'venue' => route('venue.rundown.show', $this->event),
            default => route('klien.acara.detail', $this->event).'#rundown',
        };

        return [
            'event_id' => $this->event->id,
            'message' => $message,
            'url' => $url,
        ];
    }
}
