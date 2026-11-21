<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Penyedia (Vendor/Venue) saat pesanan dikonfirmasi setelah pembayaran lunas.
 * Catatan privasi EMS: nama klien tidak ditampilkan, hanya nomor Acara #ID.
 */
class OrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderItem $orderItem,
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
        $subjectName = $this->orderItem->subject?->name ?? 'Layanan';
        $eventId = $this->orderItem->event_id;

        return [
            'order_item_id' => $this->orderItem->id,
            'event_id' => $eventId,
            'message' => "Pesanan '{$subjectName}' untuk Acara #{$eventId} telah terkonfirmasi setelah pembayaran klien divalidasi.",
        ];
    }
}
