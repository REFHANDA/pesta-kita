<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Klien dan Penyedia saat item pesanan direview oleh Admin
 * (disetujui, ditolak, atau dialihkan).
 */
class OrderItemReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderItem $orderItem,
        public string $action, // 'approved', 'rejected', 'reassigned'
        public ?string $note = null,
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
        $eventRef = "Acara #{$this->orderItem->event_id}";
        $message = match ($this->action) {
            'approved' => "Pesanan '{$subjectName}' ({$eventRef}) telah disetujui oleh Admin.",
            'rejected' => "Pesanan '{$subjectName}' ({$eventRef}) ditolak oleh Admin. Alasan: {$this->note}",
            'reassigned' => "Pesanan '{$subjectName}' ({$eventRef}) dialihkan ke penyedia lain oleh Admin.",
            default => "Status pesanan '{$subjectName}' ({$eventRef}) diperbarui: {$this->orderItem->status->value}.",
        };

        return [
            'order_item_id' => $this->orderItem->id,
            'event_id' => $this->orderItem->event_id,
            'action' => $this->action,
            'status' => $this->orderItem->status->value,
            'note' => $this->note,
            'message' => $message,
        ];
    }
}
