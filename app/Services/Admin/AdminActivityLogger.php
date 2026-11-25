<?php

namespace App\Services\Admin;

use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminActivityLogger
{
    public function log(
        AdminUser|null $admin,
        string $action,
        string $summary,
        ?Model $subject = null,
        array $meta = [],
        ?Request $request = null,
    ): AdminActivityLog {
        return AdminActivityLog::query()->create([
            'admin_user_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'summary' => $summary,
            'meta' => $meta === [] ? null : $meta,
            'ip' => $request?->ip(),
        ]);
    }
}
