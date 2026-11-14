<?php

namespace App\Jobs;

use App\Models\DatabaseNotification;
use App\Models\DeviceToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Prepares FCM/APNs push payload. Actual provider call is gated until credentials exist.
 */
class DispatchPushNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [15, 45, 90];

    public function __construct(public string $notificationId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $notification = DatabaseNotification::query()->find($this->notificationId);

        if (! $notification) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('tokenable_type', $notification->notifiable_type)
            ->where('tokenable_id', $notification->notifiable_id)
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $payload = [
            'notification_id' => $notification->id,
            'title' => $notification->title(),
            'body' => $notification->body(),
            'notification_type' => $notification->businessType()?->value,
            'reference_type' => $notification->reference_type,
            'reference_id' => $notification->reference_id,
            'deep_link' => sprintf('knooz://notifications/%s', $notification->id),
        ];

        // Provider integration point (Firebase Admin SDK / APNs).
        // We log structured payload until FCM credentials are configured.
        if (! config('services.fcm.server_key') && ! config('services.fcm.credentials')) {
            Log::info('push.notification.queued_without_provider', [
                'tokens_count' => $tokens->count(),
                'payload' => $payload,
            ]);

            return;
        }

        Log::info('push.notification.dispatch_ready', [
            'tokens_count' => $tokens->count(),
            'payload' => $payload,
        ]);
    }
}
