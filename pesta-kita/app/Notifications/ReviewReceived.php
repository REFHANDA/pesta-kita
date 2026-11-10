<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

class ReviewReceived extends Notification
{
    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->review->order_id,
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'message' => "Pesanan #{$this->review->order_id} menerima evaluasi baru.",
        ];
    }
}
