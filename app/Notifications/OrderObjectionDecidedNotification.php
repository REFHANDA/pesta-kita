<?php

namespace App\Notifications;

use App\Models\OrderObjection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Penyedia dan Klien saat keputusan keberatan telah ditetapkan oleh Admin.
 */
class OrderObjectionDecidedNotification extends Notification
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
        $statusLabel = $this->objection->status->value;

        return [
            'objection_id' => $this->objection->id,
            'order_item_id' => $this->objection->order_item_id,
            'event_id' => $orderItem?->event_id,
            'status' => $statusLabel,
            'decision_reason' => $this->objection->decision_reason,
            'message' => "Keberatan untuk '{$subjectName}' (Acara #{$orderItem?->event_id}) telah {$statusLabel} oleh Admin. Alasan: {$this->objection->decision_reason}",
        ];
    }
}
