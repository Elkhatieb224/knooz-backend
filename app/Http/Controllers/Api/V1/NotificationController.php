<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\DatabaseNotification;
use App\Models\DeviceToken;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * v1 wrapper around notification APIs with unified response envelope.
 */
class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notifications): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->integer('per_page', 20)));
        $query = $request->user()->notifications()->active();

        if ($request->filled('type')) {
            $query->ofType($request->string('type')->toString());
        }
        if ($request->string('read') === 'unread') {
            $query->unread();
        } elseif ($request->string('read') === 'read') {
            $query->read();
        }

        $paginator = $query->paginate($perPage);

        return ApiResponse::success([
            'items' => collect($paginator->items())->map(
                fn (DatabaseNotification $n) => $n->toListArray('mobile')
            ),
            'unread_count' => $notifications->unreadCount($request->user()),
        ], 'OK', 200, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function unreadCount(Request $request, NotificationService $notifications): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $notifications->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, string $id, NotificationService $notifications): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        return ApiResponse::success([
            'unread_count' => $notifications->unreadCount($request->user()),
        ], 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllRead(Request $request, NotificationService $notifications): JsonResponse
    {
        $updated = $notifications->markAllRead($request->user());

        return ApiResponse::success([
            'updated' => $updated,
            'unread_count' => 0,
        ], 'تم تعليم الكل كمقروء.');
    }

    public function open(
        Request $request,
        string $id,
        NotificationService $notifications,
    ): JsonResponse {
        $notification = $request->user()->notifications()->active()->whereKey($id)->firstOrFail();
        $notifications->markRead($notification);

        // Mobile destinations fall back to payload (Flutter deep links).
        return ApiResponse::success([
            'notification_id' => $notification->id,
            'notification_type' => $notification->businessType()?->value,
            'reference_type' => $notification->reference_type,
            'reference_id' => $notification->reference_id,
            'title' => $notification->title(),
            'body' => $notification->body(),
            'deep_link' => sprintf('knooz://mobile/notifications/%s', $notification->id),
            'unread_count' => $notifications->unreadCount($request->user()),
        ]);
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

        return ApiResponse::success(['id' => $device->id], 'تم تسجيل الجهاز.');
    }
}
