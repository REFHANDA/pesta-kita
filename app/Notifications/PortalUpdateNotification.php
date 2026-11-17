<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PortalUpdateNotification extends Notification
{
    use Queueable;

    /**
     * Create a database-only portal notification.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $url,
    ) {}

    /**
     * Persist notifications in the application inbox only.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Notification payload shown in the portal inbox.
     *
     * @return array{title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
