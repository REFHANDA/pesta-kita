<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Admin ketika ada pengajuan acara baru beserta pesanan di dalamnya.
 */
class NewOrderSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
    ) {}

    /**
     * Saluran notifikasi yang dipakai.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Representasi array untuk disimpan di tabel notifications.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'client_id' => $this->event->client_id,
            'client_name' => $this->event->client?->name ?? 'Klien',
            'order_items_count' => $this->event->orderItems()->count(),
            'message' => "Pesanan baru diajukan untuk acara {$this->event->name}.",
        ];
    }
}
