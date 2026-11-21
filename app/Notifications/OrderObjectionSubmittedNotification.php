<?php

namespace App\Notifications;

use App\Models\OrderObjection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Admin saat penyedia mengajukan keberatan atas item pesanan.
 */
class OrderObjectionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderObjection $objection,
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
        $orderItem = $this->objection->orderItem;
        $subjectName = $orderItem?->subject?->name ?? "Item #{$this->objection->order_item_id}";

        return [
            'objection_id' => $this->objection->id,
            'order_item_id' => $this->objection->order_item_id,
            'event_id' => $orderItem?->event_id,
            'category' => $this->objection->category?->value ?? 'lainnya',
            'is_emergency' => (bool) $this->objection->is_emergency,
            'message' => "Penyedia mengajukan keberatan untuk '{$subjectName}' (Kategori: {$this->objection->category?->value}).",
        ];
    }
}
