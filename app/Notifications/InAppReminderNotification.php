<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InAppReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,
        public string $title,
        public string $message,
        public ?string $toEmail = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $preview = trim(preg_replace('/\s+/', ' ', $this->message) ?? '');
        if (mb_strlen($preview) > 160) {
            $preview = mb_substr($preview, 0, 157).'…';
        }

        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'message' => $this->message,
            'preview' => $preview,
            'to_email' => $this->toEmail,
            'url' => route('notifications.index'),
            'icon' => str_contains($this->kind, 'hosting') ? 'fa-server' : 'fa-money-bill-wave',
        ];
    }
}
