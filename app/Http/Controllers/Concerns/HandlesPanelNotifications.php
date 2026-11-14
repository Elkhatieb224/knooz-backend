<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\NotificationType;
use App\Models\DatabaseNotification;
use App\Services\Notifications\DestinationResolver;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait HandlesPanelNotifications
{
    abstract protected function notificationPanel(): string;

    abstract protected function notificationUser(Request $request): Authenticatable&Model;

    abstract protected function notificationsIndexView(): string;

    protected function authorizeNotifications(Request $request): void
    {
        if ($this->notificationPanel() === 'admin') {
            $this->authorize('notifications.view');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);

        $query = $user->notifications()->active();

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('data->title', 'like', $term)
                    ->orWhere('data->body', 'like', $term)
                    ->orWhere('data->message', 'like', $term);
            });
        }

        if ($request->filled('type')) {
            $query->ofType($request->string('type')->toString());
        }

        if ($request->string('read') === 'unread') {
            $query->unread();
        } elseif ($request->string('read') === 'read') {
            $query->read();
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view($this->notificationsIndexView(), [
            'notifications' => $notifications,
            'panel' => $this->notificationPanel(),
            'types' => NotificationType::cases(),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'type' => $request->string('type')->toString(),
                'read' => $request->string('read')->toString(),
            ],
        ]);
    }

    public function preview(Request $request, NotificationService $notifications): JsonResponse
    {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);
        $limit = min(10, max(5, (int) $request->integer('limit', 8)));

        $items = $user->notifications()
            ->active()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $n) => $n->toListArray($this->notificationPanel()));

        return response()->json([
            'unread_count' => $notifications->unreadCount($user),
            'items' => $items,
        ]);
    }

    public function unreadCount(Request $request, NotificationService $notifications): JsonResponse
    {
        $this->authorizeNotifications($request);

        return response()->json([
            'unread_count' => $notifications->unreadCount($this->notificationUser($request)),
        ]);
    }

    public function open(
        Request $request,
        string $id,
        NotificationService $notifications,
        DestinationResolver $destinations,
    ): RedirectResponse {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);

        $notification = $user->notifications()->active()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        $result = $destinations->inspect($notification, $this->notificationPanel(), $user);

        if (! $result['available'] || ! $result['url']) {
            return redirect()
                ->route($this->notificationPanel().'.notifications.index')
                ->with('error', $result['reason'] ?? 'تعذر فتح وجهة الإشعار.');
        }

        return redirect()->to($result['url']);
    }

    public function markRead(Request $request, string $id, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $notifications->unreadCount($user),
            ]);
        }

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllRead(Request $request, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);
        $count = $notifications->markAllRead($user);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'updated' => $count,
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }

    public function destroy(Request $request, string $id, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        $this->authorizeNotifications($request);
        $user = $this->notificationUser($request);
        $notification = $user->notifications()->whereKey($id)->firstOrFail();
        $notifications->deleteOwned($notification);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $notifications->unreadCount($user),
            ]);
        }

        return back()->with('success', 'تم حذف الإشعار.');
    }
}
