<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\CenterUser;
use App\Models\CompanyUser;
use App\Models\User;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BroadcastNotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected AdminActivityLogger $activity,
    ) {
    }

    public function create(): View
    {
        $this->authorize('notifications.manage');

        return view('admin.notifications.broadcast', [
            'audiences' => [
                'customers' => 'عملاء التطبيق',
                'companies' => 'مستخدمو لوحة الشركات',
                'centers' => 'مستخدمو لوحة المراكز',
                'admins' => 'مسؤولو الإدارة',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('notifications.manage');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:2000'],
            'audience' => ['required', 'string', 'in:customers,companies,centers,admins'],
        ]);

        $recipients = $this->recipientsFor($data['audience']);
        $count = $this->notifications->notify(
            $recipients,
            NotificationType::System,
            $data['title'],
            $data['body'],
            eventKey: sprintf('broadcast:%s:%s', $data['audience'], now()->format('YmdHis')),
            queue: true,
        );

        $this->activity->log(
            $request->user('admin'),
            'notifications.broadcast',
            "إرسال إشعار جماعي ({$data['audience']}) — {$count} مستلم",
            meta: [
                'audience' => $data['audience'],
                'title' => $data['title'],
                'count' => $count,
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', "تم جدولة/إرسال الإشعار إلى {$count} مستلم.");
    }

    /**
     * @return Collection<int, mixed>
     */
    protected function recipientsFor(string $audience): Collection
    {
        return match ($audience) {
            'customers' => User::query()
                ->whereIn('role', [
                    UserRole::CustomerIndividual->value,
                    UserRole::CustomerCompany->value,
                ])
                ->where('status', UserStatus::Active)
                ->get(),
            'companies' => CompanyUser::query()
                ->where('status', UserStatus::Active)
                ->get(),
            'centers' => CenterUser::query()
                ->where('status', UserStatus::Active)
                ->get(),
            'admins' => AdminUser::query()
                ->where('status', UserStatus::Active)
                ->get(),
            default => collect(),
        };
    }
}
