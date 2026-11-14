<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Marker / payload helper for panel database notifications.
 * Creation is handled by NotificationService for bulk efficiency.
 */
class PanelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public NotificationType $notificationType,
        public string $title,
        public string $body,
        public ?string $referenceType = null,
        public int|string|null $referenceId = null,
        public array $meta = [],
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
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
        return [
            'title' => $this->title,
            'body' => $this->body,
            'message' => $this->body,
            'notification_type' => $this->notificationType->value,
            'icon' => $this->notificationType->icon(),
            'type_label' => $this->notificationType->label(),
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'meta' => $this->meta,
        ];
    }
}
