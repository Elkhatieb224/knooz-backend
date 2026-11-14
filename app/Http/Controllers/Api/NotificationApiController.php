<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\DatabaseNotification;
use App\Models\DeviceToken;
use App\Services\Notifications\DestinationResolver;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request, NotificationService $notifications): JsonResponse
    {
        $user = $request->user();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 20)));

        $query = $user->notifications()->active();

        if ($request->filled('type')) {
            $query->ofType($request->string('type')->toString());
        }

        if ($request->string('read') === 'unread') {
            $query->unread();
        } elseif ($request->string('read') === 'read') {
            $query->read();
        }

        $paginator = $query->paginate($perPage);
        $panel = (string) $request->attributes->get('panel', 'mobile');

        return response()->json([
            'unread_count' => $notifications->unreadCount($user),
            'data' => collect($paginator->items())->map(
                fn (DatabaseNotification $n) => $n->toListArray($panel === 'mobile' ? 'admin' : $panel)
            ),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'types' => collect(NotificationType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'icon' => $t->icon(),
            ]),
        ]);
    }

    public function unreadCount(Request $request, NotificationService $notifications): JsonResponse
    {
        return response()->json([
            'unread_count' => $notifications->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, string $id, NotificationService $notifications): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        return response()->json([
            'ok' => true,
            'unread_count' => $notifications->unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request, NotificationService $notifications): JsonResponse
    {
        $updated = $notifications->markAllRead($request->user());

        return response()->json([
            'ok' => true,
            'updated' => $updated,
            'unread_count' => 0,
        ]);
    }

    public function open(
        Request $request,
        string $id,
        NotificationService $notifications,
        DestinationResolver $destinations,
    ): JsonResponse {
        $panel = (string) $request->attributes->get('panel', 'admin');
        $notification = $request->user()->notifications()->active()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        $result = $destinations->inspect($notification, $panel, $request->user());

        return response()->json([
            'ok' => $result['available'],
            'url' => $result['url'],
            'reason' => $result['reason'],
            'deep_link' => sprintf('knooz://%s/notifications/%s', $panel, $notification->id),
            'payload' => [
                'notification_id' => $notification->id,
                'notification_type' => $notification->businessType()?->value,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id,
            ],
            'unread_count' => $notifications->unreadCount($request->user()),
        ], $result['available'] ? 200 : 409);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:32'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'app' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user();

        $device = DeviceToken::query()->updateOrCreate(
            [
                'tokenable_type' => $user->getMorphClass(),
                'tokenable_id' => $user->getKey(),
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'app' => $data['app'] ?? 'mobile',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['ok' => true, 'id' => $device->id]);
    }
}
