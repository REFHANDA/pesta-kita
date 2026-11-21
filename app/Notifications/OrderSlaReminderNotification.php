<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi pengingat ke Admin saat pesanan mendekati atau melewati SLA review (24 jam).
 */
class OrderSlaReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderItem $orderItem,
        public bool $isOverdue = false,
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
        $subjectName = $this->orderItem->subject?->name ?? "Pesanan #{$this->orderItem->id}";
        $eventName = $this->orderItem->event?->name ?? "Acara #{$this->orderItem->event_id}";
        $dueFmt = $this->orderItem->review_due_at?->format('d/m/Y H:i') ?? '-';

        $message = $this->isOverdue
            ? "MENDESAK: Pesanan '{$subjectName}' untuk {$eventName} telah MELEWATI batas SLA review ({$dueFmt} WIB)."
            : "Pengingat SLA: Pesanan '{$subjectName}' untuk {$eventName} mendekati batas waktu review ({$dueFmt} WIB).";

        return [
            'order_item_id' => $this->orderItem->id,
            'event_id' => $this->orderItem->event_id,
            'review_due_at' => $this->orderItem->review_due_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue,
            'message' => $message,
        ];
    }
}
