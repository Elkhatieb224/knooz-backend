<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Enums\UserStatus;
use App\Jobs\DispatchPushNotificationJob;
use App\Jobs\SendBulkPanelNotificationsJob;
use App\Models\AdminUser;
use App\Models\CenterUser;
use App\Models\CompanyUser;
use App\Models\DatabaseNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(
        protected DestinationResolver $destinations
    ) {
    }

    /**
     * @param  Authenticatable|Model|iterable<int, Authenticatable|Model>  $recipients
     * @param  array<string, mixed>  $meta
     */
    public function notify(
        Authenticatable|Model|iterable $recipients,
        NotificationType $type,
        string $title,
        string $body,
        ?Model $reference = null,
        ?string $eventKey = null,
        array $meta = [],
        bool $queue = true,
    ): int {
        $list = $this->normalizeRecipients($recipients);

        if ($list->isEmpty()) {
            return 0;
        }

        if ($eventKey && DatabaseNotification::query()->where('event_key', $eventKey)->exists()) {
            return 0;
        }

        if ($queue && $list->count() > 1) {
            SendBulkPanelNotificationsJob::dispatch(
                recipients: $list->map(fn (Model $r) => [
                    'type' => $r->getMorphClass(),
                    'id' => $r->getKey(),
                ])->all(),
                notificationType: $type->value,
                title: $title,
                body: $body,
                referenceType: $reference?->getMorphClass(),
                referenceId: $reference?->getKey(),
                eventKey: $eventKey,
                meta: $meta,
            );

            return $list->count();
        }

        return $this->storeMany($list, $type, $title, $body, $reference, $eventKey, $meta);
    }

    /**
     * @param  Collection<int, Model>  $recipients
     * @param  array<string, mixed>  $meta
     */
    public function storeMany(
        Collection $recipients,
        NotificationType $type,
        string $title,
        string $body,
        ?Model $reference = null,
        ?string $eventKey = null,
        array $meta = [],
    ): int {
        if ($recipients->isEmpty()) {
            return 0;
        }

        if ($eventKey && DatabaseNotification::query()->where('event_key', $eventKey)->exists()) {
            return 0;
        }

        $now = now();
        $rows = [];
        $createdIds = [];

        foreach ($recipients as $index => $recipient) {
            $id = (string) Str::uuid();
            $createdIds[] = [
                'id' => $id,
                'type' => $recipient->getMorphClass(),
                'key' => $recipient->getKey(),
            ];

            $payload = [
                'title' => $title,
                'body' => $body,
                'message' => $body,
                'notification_type' => $type->value,
                'icon' => $type->icon(),
                'type_label' => $type->label(),
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'meta' => $meta,
            ];

            $rows[] = [
                'id' => $id,
                'type' => \App\Notifications\PanelNotification::class,
                'notifiable_type' => $recipient->getMorphClass(),
                'notifiable_id' => $recipient->getKey(),
                'notification_type' => $type->value,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'event_key' => $eventKey && $index === 0 ? $eventKey : ($eventKey ? $eventKey.':'.$recipient->getKey() : null),
                'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'read_at' => null,
                'archived_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('notifications')->insert($rows);

        foreach ($createdIds as $item) {
            $this->forgetUnreadCache($item['type'], $item['key']);
        }

        foreach ($createdIds as $item) {
            DispatchPushNotificationJob::dispatch($item['id']);
        }

        return count($rows);
    }

    public function unreadCount(Model $user): int
    {
        $key = $this->unreadCacheKey($user->getMorphClass(), $user->getKey());

        return (int) Cache::remember($key, now()->addSeconds(30), function () use ($user) {
            return $user->notifications()->active()->unread()->count();
        });
    }

    public function markRead(DatabaseNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
            $this->forgetUnreadCache($notification->notifiable_type, $notification->notifiable_id);
        }
    }

    public function markAllRead(Model $user): int
    {
        $updated = $user->notifications()
            ->active()
            ->unread()
            ->update(['read_at' => now()]);

        $this->forgetUnreadCache($user->getMorphClass(), $user->getKey());

        return (int) $updated;
    }

    public function deleteOwned(DatabaseNotification $notification): void
    {
        $type = $notification->notifiable_type;
        $id = $notification->notifiable_id;
        $notification->delete();
        $this->forgetUnreadCache($type, $id);
    }

    /**
     * @return Collection<int, AdminUser>
     */
    public function activeAdmins(?string $permission = null): Collection
    {
        $query = AdminUser::query()->where('status', UserStatus::Active);

        $admins = $query->get();

        if ($permission === null) {
            return $admins;
        }

        return $admins->filter(fn (AdminUser $admin) => \App\Support\Permissions::allows($admin, $permission))->values();
    }

    /**
     * @return Collection<int, CompanyUser>
     */
    public function companyUsers(int $companyId): Collection
    {
        return CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('status', UserStatus::Active)
            ->get();
    }

    /**
     * @return Collection<int, CenterUser>
     */
    public function centerUsers(int $centerId): Collection
    {
        return CenterUser::query()
            ->where('center_id', $centerId)
            ->where('status', UserStatus::Active)
            ->get();
    }

    public function forgetUnreadCache(string $morphType, int|string $id): void
    {
        Cache::forget($this->unreadCacheKey($morphType, $id));
    }

    protected function unreadCacheKey(string $morphType, int|string $id): string
    {
        return sprintf('notif:unread:%s:%s', $morphType, $id);
    }

    /**
     * @param  Authenticatable|Model|iterable<int, Authenticatable|Model>  $recipients
     * @return Collection<int, Model>
     */
    protected function normalizeRecipients(Authenticatable|Model|iterable $recipients): Collection
    {
        if ($recipients instanceof Model) {
            return collect([$recipients]);
        }

        return collect($recipients)
            ->filter(fn ($item) => $item instanceof Model)
            ->unique(fn (Model $item) => $item->getMorphClass().':'.$item->getKey())
            ->values();
    }
}
