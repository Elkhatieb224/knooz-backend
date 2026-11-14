<?php

namespace App\Models\Concerns;

use App\Models\DatabaseNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait UsesKnoozNotifications
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    public function readNotifications(): MorphMany
    {
        return $this->notifications()->read();
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->unread();
    }
}
