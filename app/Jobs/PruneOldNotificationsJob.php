<?php

namespace App\Jobs;

use App\Models\DatabaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PruneOldNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $retainDays = 180,
        public int $archiveAfterDays = 90,
    ) {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $archived = DatabaseNotification::query()
            ->whereNull('archived_at')
            ->where('created_at', '<', now()->subDays($this->archiveAfterDays))
            ->update(['archived_at' => now()]);

        $deleted = DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($this->retainDays))
            ->whereNotNull('read_at')
            ->delete();

        Log::info('notifications.prune', [
            'archived' => $archived,
            'deleted' => $deleted,
        ]);
    }
}
