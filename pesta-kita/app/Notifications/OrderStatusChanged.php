<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => "Status pesanan #{$this->order->id} berubah menjadi {$this->order->status}.",
        ];
    }
}
