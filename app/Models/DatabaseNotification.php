<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'notification_type',
        'reference_type',
        'reference_id',
        'event_key',
        'data',
        'read_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function businessType(): ?NotificationType
    {
        $raw = $this->notification_type ?? ($this->data['notification_type'] ?? null);

        return is_string($raw) ? NotificationType::tryFrom($raw) : null;
    }

    public function title(): string
    {
        return (string) ($this->data['title'] ?? $this->data['subject'] ?? 'إشعار');
    }

    public function body(): string
    {
        return (string) ($this->data['body'] ?? $this->data['message'] ?? '');
    }

    public function icon(): string
    {
        return (string) ($this->data['icon'] ?? $this->businessType()?->icon() ?? 'bi-bell');
    }

    public function typeLabel(): string
    {
        return $this->businessType()?->label() ?? 'إشعار';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType(Builder $query, string|NotificationType $type): Builder
    {
        $value = $type instanceof NotificationType ? $type->value : $type;

        return $query->where(function (Builder $inner) use ($value) {
            $inner->where('notification_type', $value)
                ->orWhere('data->notification_type', $value);
        });
    }

    /**
     * Compact payload for dropdown / mobile list.
     *
     * @return array<string, mixed>
     */
    public function toListArray(string $panel): array
    {
        $openUrl = null;
        if (in_array($panel, ['admin', 'company', 'center'], true) && \Illuminate\Support\Facades\Route::has($panel.'.notifications.open')) {
            $openUrl = route($panel.'.notifications.open', $this->id);
        }

        $mobileRoute = null;
        if ($panel === 'mobile') {
            $mobileRoute = app(\App\Services\Notifications\MobileDestinationResolver::class)
                ->resolve($this)['route'];
        }

        return [
            'id' => $this->id,
            'title' => $this->title(),
            'body' => mb_substr($this->body(), 0, 160),
            'notification_type' => $this->businessType()?->value ?? 'system',
            'type_label' => $this->typeLabel(),
            'icon' => $this->icon(),
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'route' => $mobileRoute,
            'open_url' => $openUrl,
            'deep_link' => $mobileRoute
                ? sprintf('knooz://mobile%s', $mobileRoute)
                : sprintf('knooz://%s/notifications/%s', $panel, $this->id),
        ];
    }
}
