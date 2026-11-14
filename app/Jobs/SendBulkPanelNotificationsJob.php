<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkPanelNotificationsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  list<array{type: string, id: int|string}>  $recipients
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public array $recipients,
        public string $notificationType,
        public string $title,
        public string $body,
        public ?string $referenceType = null,
        public int|string|null $referenceId = null,
        public ?string $eventKey = null,
        public array $meta = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notifications): void
    {
        $type = NotificationType::from($this->notificationType);

        $models = collect($this->recipients)
            ->map(function (array $row) {
                if (! class_exists($row['type'])) {
                    return null;
                }

                return $row['type']::query()->find($row['id']);
            })
            ->filter()
            ->values();

        $reference = null;
        if ($this->referenceType && $this->referenceId && class_exists($this->referenceType)) {
            $reference = $this->referenceType::query()->find($this->referenceId);
        }

        $notifications->storeMany(
            $models,
            $type,
            $this->title,
            $this->body,
            $reference,
            $this->eventKey,
            $this->meta,
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SendBulkPanelNotificationsJob failed', [
            'event_key' => $this->eventKey,
            'type' => $this->notificationType,
            'error' => $exception?->getMessage(),
        ]);
    }
}
