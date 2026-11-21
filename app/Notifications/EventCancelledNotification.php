<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi acara dibatalkan sebelum pembayaran. Payload untuk penyedia hanya
 * memuat "Acara #ID" tanpa nama klien.
 */
class EventCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public string $message,
        public ?int $orderItemId = null,
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
        return array_filter([
            'event_id' => $this->event->id,
            'order_item_id' => $this->orderItemId,
            'message' => $this->message,
        ], fn (mixed $value): bool => $value !== null);
    }
}
