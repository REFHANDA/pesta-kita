<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi evaluasi acara dikirim (Tahap C6). Payload penyedia hanya
 * "Acara #ID" tanpa nama klien dan tanpa isi komentar; komentar dibaca
 * di halaman, bukan di notifikasi.
 */
class EvaluationSubmittedNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $audience  admin|provider
     */
    public function __construct(
        public Event $event,
        public string $audience = 'admin',
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
            'event_id' => $this->event->id,
            'message' => "Acara #{$this->event->id} telah dinilai oleh klien.",
            'url' => $this->audience === 'admin'
                ? route('admin.acara.detail', $this->event).'#evaluasi'
                : route($notifiable->role->value.'.beranda'),
        ];
    }
}
